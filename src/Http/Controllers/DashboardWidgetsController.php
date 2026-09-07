<?php

namespace HolartWeb\AxoraCMS\Http\Controllers;

use HolartWeb\AxoraCMS\Models\TDashboardWidget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DashboardWidgetsController extends Controller
{
    /**
     * Get user's dashboard widgets
     */
    public function index(): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        $widgets = TDashboardWidget::where('admin_id', $admin->id)
            ->where('is_visible', true)
            ->orderBy('position')
            ->get();

        // If dashboard not initialized yet, create default widgets
        if (! $admin->dashboard_initialized) {
            $defaultWidgets = TDashboardWidget::getDefaultWidgets();
            foreach ($defaultWidgets as $widget) {
                TDashboardWidget::create([
                    'admin_id' => $admin->id,
                    ...$widget,
                ]);
            }

            // Mark dashboard as initialized
            $admin->dashboard_initialized = true;
            $admin->save();

            $widgets = TDashboardWidget::where('admin_id', $admin->id)
                ->where('is_visible', true)
                ->orderBy('position')
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $widgets,
        ]);
    }

    /**
     * Get available widget types
     */
    public function availableTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => TDashboardWidget::getAvailableTypes(),
        ]);
    }

    /**
     * Add widget to dashboard
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'widget_type' => 'required|string|max:50',
            'position' => 'nullable|integer|min:0',
            'width' => 'nullable|integer|min:1|max:12',
            'settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $admin = Auth::guard('admin')->user();

        // Get max position if not provided
        $position = $request->input('position');
        if ($position === null) {
            $maxPosition = TDashboardWidget::where('admin_id', $admin->id)->max('position');
            $position = $maxPosition ? $maxPosition + 1 : 1;
        }

        $widget = TDashboardWidget::create([
            'admin_id' => $admin->id,
            'widget_type' => $request->input('widget_type'),
            'position' => $position,
            'width' => $request->input('width', 6),
            'settings' => $request->input('settings', []),
            'is_visible' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Виджет успешно добавлен',
            'data' => $widget,
        ], 201);
    }

    /**
     * Update widget
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        $widget = TDashboardWidget::where('id', $id)
            ->where('admin_id', $admin->id)
            ->first();

        if (! $widget) {
            return response()->json([
                'success' => false,
                'message' => 'Виджет не найден',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'position' => 'nullable|integer|min:0',
            'width' => 'nullable|integer|min:1|max:12',
            'is_visible' => 'nullable|boolean',
            'settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $widget->update($request->only(['position', 'width', 'is_visible', 'settings']));

        return response()->json([
            'success' => true,
            'message' => 'Виджет успешно обновлен',
            'data' => $widget,
        ]);
    }

    /**
     * Delete widget
     */
    public function destroy(int $id): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        $widget = TDashboardWidget::where('id', $id)
            ->where('admin_id', $admin->id)
            ->first();

        if (! $widget) {
            return response()->json([
                'success' => false,
                'message' => 'Виджет не найден',
            ], 404);
        }

        $widget->delete();

        return response()->json([
            'success' => true,
            'message' => 'Виджет успешно удален',
        ]);
    }

    /**
     * Reorder widgets
     */
    public function reorder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'widgets' => 'required|array',
            'widgets.*.id' => 'required|integer',
            'widgets.*.position' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $admin = Auth::guard('admin')->user();

        foreach ($request->input('widgets') as $widgetData) {
            TDashboardWidget::where('id', $widgetData['id'])
                ->where('admin_id', $admin->id)
                ->update(['position' => $widgetData['position']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Порядок виджетов успешно обновлен',
        ]);
    }

    /**
     * Reset widgets to default
     */
    public function reset(): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        // Delete all existing widgets
        TDashboardWidget::where('admin_id', $admin->id)->delete();

        // Create default widgets
        $defaultWidgets = TDashboardWidget::getDefaultWidgets();
        foreach ($defaultWidgets as $widget) {
            TDashboardWidget::create([
                'admin_id' => $admin->id,
                ...$widget,
            ]);
        }

        $widgets = TDashboardWidget::where('admin_id', $admin->id)
            ->orderBy('position')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Виджеты сброшены к настройкам по умолчанию',
            'data' => $widgets,
        ]);
    }
}
