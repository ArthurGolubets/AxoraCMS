<?php

namespace HolartWeb\AxoraCMS\Http\Controllers;

use HolartWeb\AxoraCMS\Models\Callback\TComments;
use HolartWeb\AxoraCMS\Models\Callback\TCustomFormSubmission;
use HolartWeb\AxoraCMS\Models\Callback\TUserRequests;
use HolartWeb\AxoraCMS\Models\Commerce\TOrders;
use HolartWeb\AxoraCMS\Models\TAdminAction;
use HolartWeb\AxoraCMS\Models\TAdminNotification;
use HolartWeb\AxoraCMS\Models\TAdminNotificationRead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Bell-icon notifications + the "new orders" counter shown on the sidebar's
 * "Список заказов" link. Bundled behind one endpoint so the header only
 * needs a single poll.
 */
class AdminNotificationsController extends Controller
{
    /**
     * Latest notifications for the current admin, with read state, plus the
     * counters the header chrome needs.
     */
    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('t_admin_notifications')) {
            return response()->json([
                'notifications' => [],
                'unread_count' => 0,
                'new_orders_count' => $this->newOrdersCount(),
                'counters' => $this->counters(),
            ]);
        }

        $adminId = Auth::guard('admin')->id();
        $limit = min((int) $request->get('limit', 20), 50);

        $readIds = TAdminNotificationRead::where('administrator_id', $adminId)
            ->pluck('notification_id');

        $notifications = TAdminNotification::orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (TAdminNotification $notification) use ($readIds) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'data' => $notification->data,
                    'link' => $notification->link,
                    'created_at' => $notification->created_at,
                    'is_read' => $readIds->contains($notification->id),
                ];
            });

        $unreadCount = TAdminNotification::whereNotIn('id', $readIds)->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'new_orders_count' => $this->newOrdersCount(),
            'counters' => $this->counters(),
        ]);
    }

    /**
     * Mark one notification as read for the current admin.
     */
    public function markRead($id): JsonResponse
    {
        $adminId = Auth::guard('admin')->id();

        TAdminNotificationRead::firstOrCreate([
            'notification_id' => $id,
            'administrator_id' => $adminId,
        ], [
            'read_at' => now(),
        ]);

        return response()->json(['message' => 'Отмечено как прочитанное']);
    }

    /**
     * Mark every current notification as read for the current admin.
     */
    public function markAllRead(): JsonResponse
    {
        $adminId = Auth::guard('admin')->id();

        $alreadyRead = TAdminNotificationRead::where('administrator_id', $adminId)->pluck('notification_id');

        $unreadIds = TAdminNotification::whereNotIn('id', $alreadyRead)->pluck('id');

        $rows = $unreadIds->map(fn ($id) => [
            'notification_id' => $id,
            'administrator_id' => $adminId,
            'read_at' => now(),
        ])->all();

        if (! empty($rows)) {
            TAdminNotificationRead::insert($rows);
        }

        return response()->json(['message' => 'Все уведомления отмечены как прочитанные']);
    }

    /**
     * Delete every notification. Notifications are shared across every
     * administrator, so this clears the bell for everyone — not just the
     * caller. It has no effect on the "new orders" counter, which is driven
     * by order status, not by the notification log.
     */
    public function clear(): JsonResponse
    {
        if (Schema::hasTable('t_admin_notifications')) {
            // t_admin_notification_reads cascades on delete via FK.
            TAdminNotification::query()->delete();
        }

        if (class_exists(TAdminAction::class)) {
            TAdminAction::log('deleted', 'notification', null, 'Очищены все уведомления');
        }

        return response()->json(['message' => 'Уведомления очищены']);
    }

    /**
     * Orders that still need attention (fresh, unprocessed) — drives the
     * badge next to "Список заказов" in the sidebar independently of
     * notification read state.
     */
    protected function newOrdersCount(): int
    {
        if (! Schema::hasTable('t_orders')) {
            return 0;
        }

        return TOrders::where('delivery_status', TOrders::DELIVERY_PENDING)->count();
    }

    /**
     * Sidebar badge counters: items that still need an administrator's attention.
     *
     * @return array{orders: int, comments: int, user_requests: int, custom_forms: int}
     */
    protected function counters(): array
    {
        return [
            'orders' => $this->newOrdersCount(),
            'comments' => Schema::hasTable('t_comments')
                ? TComments::where('is_moderated', false)->count()
                : 0,
            'user_requests' => Schema::hasColumn('t_user_requests', 'viewed_at')
                ? TUserRequests::whereNull('viewed_at')->count()
                : 0,
            'custom_forms' => Schema::hasTable('t_custom_form_submissions')
                ? TCustomFormSubmission::whereNull('viewed_at')->count()
                : 0,
        ];
    }
}
