<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\MessageManager;

use HolartWeb\AxoraCMS\Models\MessageManager\TMessageAction;
use HolartWeb\AxoraCMS\Models\MessageManager\TMessageTemplate;
use HolartWeb\AxoraCMS\Services\MessageManager\MessageEvents;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class TemplatesController extends Controller
{
    public function index(): JsonResponse
    {
        $usage = $this->usageCounts();

        $templates = TMessageTemplate::orderBy('name')->get()->map(
            fn (TMessageTemplate $template) => $template->toArray() + ['usage' => $usage[$template->id] ?? 0]
        );

        return response()->json(['data' => $templates]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => TMessageTemplate::findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $template = TMessageTemplate::create($this->validated($request));

        return response()->json(['success' => true, 'data' => $template], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $template = TMessageTemplate::findOrFail($id);
        $template->update($this->validated($request));

        return response()->json(['success' => true, 'data' => $template]);
    }

    public function duplicate(int $id): JsonResponse
    {
        $source = TMessageTemplate::findOrFail($id);
        $copy = TMessageTemplate::create($source->only(['event', 'subject', 'body', 'blocks', 'is_active']) + ['name' => $source->name.' (копия)']);

        return response()->json(['success' => true, 'data' => $copy], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $template = TMessageTemplate::findOrFail($id);
        $used = $this->usageCounts()[$template->id] ?? 0;

        if ($used > 0) {
            return response()->json([
                'success' => false,
                'message' => "Шаблон используется в действиях менеджера сообщений ({$used}). Сначала выберите в них другой шаблон.",
            ], 422);
        }

        $template->delete();

        return response()->json(['success' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'event' => ['nullable', 'string', Rule::in(array_keys(MessageEvents::all()))],
            'subject' => 'required|string|max:500',
            'body' => 'required|string|max:500000',
            'blocks' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ], [
            'name.required' => 'Введите название шаблона',
            'subject.required' => 'Введите тему письма',
            'body.required' => 'Добавьте в письмо хотя бы один блок',
        ]);
    }

    /**
     * template id => number of email actions using it.
     *
     * @return array<int, int>
     */
    protected function usageCounts(): array
    {
        $counts = [];

        TMessageAction::where('channel', TMessageAction::CHANNEL_EMAIL)->get(['id', 'config'])->each(function (TMessageAction $action) use (&$counts) {
            $id = (int) ($action->config['template_id'] ?? 0);

            if ($id) {
                $counts[$id] = ($counts[$id] ?? 0) + 1;
            }
        });

        return $counts;
    }
}
