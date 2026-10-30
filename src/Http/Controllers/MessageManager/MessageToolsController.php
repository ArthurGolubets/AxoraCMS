<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\MessageManager;

use HolartWeb\AxoraCMS\Models\MessageManager\TMessageAction;
use HolartWeb\AxoraCMS\Models\MessageManager\TMessageLog;
use HolartWeb\AxoraCMS\Models\MessageManager\TMessageTemplate;
use HolartWeb\AxoraCMS\Services\MessageManager\MessageEvents;
use HolartWeb\AxoraCMS\Services\MessageManager\MessageManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

/**
 * Preview, test send and delivery log of the "Менеджер сообщений" module.
 */
class MessageToolsController extends Controller
{
    public function __construct(protected MessageManager $manager) {}

    /**
     * Render text/template with example values.
     */
    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event' => ['nullable', 'string', Rule::in(array_keys(MessageEvents::all()))],
            'subject' => 'nullable|string|max:500',
            'body' => 'nullable|string|max:500000',
            'text' => 'nullable|string|max:4000',
        ]);

        $values = MessageEvents::samples($data['event'] ?? null);

        return response()->json([
            'subject' => $this->manager->render((string) ($data['subject'] ?? ''), $values),
            'body' => $this->manager->render((string) ($data['body'] ?? ''), $values, MessageEvents::sampleHtml($data['event'] ?? null), 'html'),
            'text' => $this->manager->render((string) ($data['text'] ?? ''), $values, [], 'telegram'),
        ]);
    }

    /**
     * Send a real message built from example values.
     */
    public function test(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel' => ['required', Rule::in([TMessageAction::CHANNEL_TELEGRAM, TMessageAction::CHANNEL_EMAIL])],
            'event' => ['nullable', 'string', Rule::in(array_keys(MessageEvents::all()))],
            'config' => 'required|array',
            'email' => 'nullable|email',
        ]);

        if ($data['channel'] === TMessageAction::CHANNEL_EMAIL) {
            if (empty($data['email'])) {
                return response()->json(['success' => false, 'message' => 'Укажите email для тестового письма'], 422);
            }

            if (! TMessageTemplate::whereKey($data['config']['template_id'] ?? 0)->exists()) {
                return response()->json(['success' => false, 'message' => 'Сначала выберите шаблон письма'], 422);
            }
        }

        $result = $this->manager->sendTest($data['channel'], $data['config'], $data['event'] ?? null, $data['email'] ?? null);
        $sent = $result['status'] === TMessageLog::STATUS_SENT;

        return response()->json([
            'success' => $sent,
            'message' => $sent ? 'Тестовое сообщение отправлено' : ($result['error'] ?: 'Сообщение не отправлено'),
        ], $sent ? 200 : 422);
    }

    public function logs(Request $request): JsonResponse
    {
        $query = TMessageLog::query()->orderByDesc('id');

        foreach (['status', 'channel', 'event'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->where(fn ($q) => $q->where('recipient', 'like', $search)->orWhere('subject', 'like', $search)->orWhere('body', 'like', $search));
        }

        $logs = $query->paginate(min(100, max(10, (int) $request->input('per_page', 30))));
        $events = MessageEvents::all();

        return response()->json([
            'data' => collect($logs->items())->map(fn (TMessageLog $log) => $log->toArray() + [
                'event_label' => $events[$log->event]['label'] ?? ($log->event === 'test' ? 'Тест' : $log->event),
            ])->values(),
            'meta' => ['current_page' => $logs->currentPage(), 'last_page' => $logs->lastPage(), 'total' => $logs->total()],
        ]);
    }

    public function clearLogs(): JsonResponse
    {
        TMessageLog::query()->delete();

        return response()->json(['success' => true]);
    }
}
