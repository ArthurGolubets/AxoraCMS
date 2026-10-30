<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\MessageManager;

use HolartWeb\AxoraCMS\Models\Integrations\TIntegrationSettings;
use HolartWeb\AxoraCMS\Models\MessageManager\TMessageAction;
use HolartWeb\AxoraCMS\Models\MessageManager\TMessageSubscription;
use HolartWeb\AxoraCMS\Models\MessageManager\TMessageTemplate;
use HolartWeb\AxoraCMS\Models\TModule;
use HolartWeb\AxoraCMS\Services\Integrations\TelegramService;
use HolartWeb\AxoraCMS\Services\Mail\MailSettingsService;
use HolartWeb\AxoraCMS\Services\MessageManager\MessageEvents;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubscriptionsController extends Controller
{
    public const OPERATORS = ['eq', 'neq', 'contains', 'not_contains', 'gt', 'lt', 'empty', 'not_empty'];

    /**
     * Everything the editor needs: events with their variables, channels and templates.
     */
    public function meta(): JsonResponse
    {
        $events = [];

        foreach (MessageEvents::available() as $code => $event) {
            $events[] = [
                'code' => $code,
                'label' => $event['label'],
                'group' => $event['group'],
                'variables' => MessageEvents::describe(MessageEvents::variablesFor($code)),
            ];
        }

        $telegramInstalled = TModule::isInstalled('telegram');

        return response()->json([
            'events' => $events,
            'common_variables' => MessageEvents::describe(MessageEvents::COMMON_VARIABLES),
            'channels' => [
                'telegram' => [
                    'installed' => $telegramInstalled,
                    'configured' => $telegramInstalled && (new TelegramService)->isConfigured(),
                    'chat_ids' => $telegramInstalled ? array_values(array_map('strval', (array) TIntegrationSettings::get('telegram', 'chat_ids', []))) : [],
                ],
                'email' => [
                    'configured' => app(MailSettingsService::class)->isConfigured(),
                ],
            ],
            'templates' => TMessageTemplate::orderBy('name')->get(['id', 'name', 'event', 'is_active']),
        ]);
    }

    public function index(): JsonResponse
    {
        $subscriptions = TMessageSubscription::with('actions')->orderBy('event')->orderBy('sort')->orderBy('id')->get();

        return response()->json(['data' => $subscriptions->map(fn ($subscription) => $this->present($subscription))->values()]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => $this->present(TMessageSubscription::with('actions')->findOrFail($id))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $subscription = DB::transaction(function () use ($data) {
            $subscription = TMessageSubscription::create([
                'name' => $data['name'],
                'event' => $data['event'],
                'is_active' => $data['is_active'] ?? true,
                'conditions' => $this->cleanConditions($data['conditions'] ?? []),
                'sort' => (int) TMessageSubscription::max('sort') + 1,
            ]);
            $this->syncActions($subscription, $data['actions'] ?? []);

            return $subscription;
        });

        return response()->json(['success' => true, 'data' => $this->present($subscription->load('actions'))], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $subscription = TMessageSubscription::findOrFail($id);
        $data = $this->validated($request);

        DB::transaction(function () use ($subscription, $data) {
            $subscription->update([
                'name' => $data['name'],
                'event' => $data['event'],
                'is_active' => $data['is_active'] ?? $subscription->is_active,
                'conditions' => $this->cleanConditions($data['conditions'] ?? []),
            ]);
            $this->syncActions($subscription, $data['actions'] ?? []);
        });

        return response()->json(['success' => true, 'data' => $this->present($subscription->load('actions'))]);
    }

    public function toggle(int $id): JsonResponse
    {
        $subscription = TMessageSubscription::findOrFail($id);
        $subscription->update(['is_active' => ! $subscription->is_active]);

        return response()->json(['success' => true, 'is_active' => $subscription->is_active]);
    }

    public function duplicate(int $id): JsonResponse
    {
        $source = TMessageSubscription::with('actions')->findOrFail($id);

        $copy = DB::transaction(function () use ($source) {
            $copy = TMessageSubscription::create([
                'name' => $source->name.' (копия)',
                'event' => $source->event,
                'is_active' => false,
                'conditions' => $source->conditions,
                'sort' => (int) TMessageSubscription::max('sort') + 1,
            ]);

            foreach ($source->actions as $action) {
                $copy->actions()->create($action->only(['channel', 'is_active', 'sort', 'delay_seconds', 'config']));
            }

            return $copy;
        });

        return response()->json(['success' => true, 'data' => $this->present($copy->load('actions'))], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        TMessageSubscription::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'event' => ['required', 'string', Rule::in(array_keys(MessageEvents::all()))],
            'is_active' => 'sometimes|boolean',
            'conditions' => 'nullable|array|max:20',
            'conditions.*.variable' => 'required|string|max:100',
            'conditions.*.operator' => ['required', Rule::in(self::OPERATORS)],
            'conditions.*.value' => 'nullable|string|max:500',
            'actions' => 'nullable|array|max:50',
            'actions.*.id' => 'nullable|integer',
            'actions.*.channel' => ['required', Rule::in([TMessageAction::CHANNEL_TELEGRAM, TMessageAction::CHANNEL_EMAIL])],
            'actions.*.is_active' => 'sometimes|boolean',
            'actions.*.delay_seconds' => 'nullable|integer|min:0|max:2592000',
            'actions.*.config' => 'required|array',
        ], [
            'name.required' => 'Введите название',
            'event.required' => 'Выберите событие',
        ]);

        foreach ($data['actions'] ?? [] as $index => $action) {
            if ($action['channel'] === TMessageAction::CHANNEL_TELEGRAM) {
                $this->validateConfig($action['config'], [
                    'text' => 'required|string|max:4000',
                    'chat_ids' => 'nullable|array',
                    'chat_ids.*' => 'string|max:100',
                ], $index);
            } else {
                $this->validateConfig($action['config'], [
                    'template_id' => 'required|integer|exists:t_message_templates,id',
                    'recipients' => 'required|string|max:1000',
                ], $index);
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, string>  $rules
     */
    protected function validateConfig(array $config, array $rules, int $index): void
    {
        $validator = validator($config, $rules, [
            'text.required' => 'Введите текст сообщения',
            'template_id.required' => 'Выберите шаблон письма',
            'template_id.exists' => 'Выбранный шаблон письма не существует',
            'recipients.required' => 'Укажите получателей письма',
        ]);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->messages() as $field => $messages) {
                $errors["actions.{$index}.config.{$field}"] = $messages;
            }

            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $conditions
     * @return array<int, array{variable: string, operator: string, value: string}>
     */
    protected function cleanConditions(array $conditions): array
    {
        return array_values(array_map(fn (array $condition) => [
            'variable' => trim((string) $condition['variable'], '# '),
            'operator' => $condition['operator'],
            'value' => (string) ($condition['value'] ?? ''),
        ], $conditions));
    }

    /**
     * Replace the action list: update known ids, create new, delete the rest; order = array order.
     *
     * @param  array<int, array<string, mixed>>  $actions
     */
    protected function syncActions(TMessageSubscription $subscription, array $actions): void
    {
        $existing = $subscription->actions()->get()->keyBy('id');
        $keep = [];

        foreach (array_values($actions) as $position => $payload) {
            $attributes = [
                'channel' => $payload['channel'],
                'is_active' => $payload['is_active'] ?? true,
                'sort' => $position,
                'delay_seconds' => (int) ($payload['delay_seconds'] ?? 0),
                'config' => $this->cleanConfig($payload['channel'], $payload['config']),
            ];

            $action = isset($payload['id']) ? $existing->get($payload['id']) : null;

            if ($action) {
                $action->update($attributes);
            } else {
                $action = $subscription->actions()->create($attributes);
            }

            $keep[] = $action->id;
        }

        $subscription->actions()->whereNotIn('id', $keep)->delete();
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function cleanConfig(string $channel, array $config): array
    {
        if ($channel === TMessageAction::CHANNEL_TELEGRAM) {
            return [
                'text' => (string) $config['text'],
                'chat_ids' => array_values(array_map('strval', $config['chat_ids'] ?? [])),
            ];
        }

        return [
            'template_id' => (int) $config['template_id'],
            'recipients' => trim((string) $config['recipients']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function present(TMessageSubscription $subscription): array
    {
        return [
            'id' => $subscription->id,
            'name' => $subscription->name,
            'event' => $subscription->event,
            'event_label' => MessageEvents::all()[$subscription->event]['label'] ?? $subscription->event,
            'is_active' => $subscription->is_active,
            'conditions' => $subscription->conditions ?? [],
            'actions' => $subscription->actions->map(fn (TMessageAction $action) => [
                'id' => $action->id,
                'channel' => $action->channel,
                'is_active' => $action->is_active,
                'delay_seconds' => $action->delay_seconds,
                'config' => $action->config ?? [],
            ])->values(),
        ];
    }
}
