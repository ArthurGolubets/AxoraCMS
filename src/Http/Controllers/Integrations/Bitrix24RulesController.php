<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Integrations;

use HolartWeb\AxoraCMS\Models\Callback\TCustomForm;
use HolartWeb\AxoraCMS\Models\Integrations\TBitrix24Rule;
use HolartWeb\AxoraCMS\Services\Integrations\Bitrix24\Bitrix24Sync;
use HolartWeb\AxoraCMS\Services\MessageManager\MessageEvents;
use HolartWeb\AxoraCMS\Services\SiteCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class Bitrix24RulesController extends Controller
{
    /**
     * Starting mapping for a new rule, ready to send as is.
     */
    private const DEFAULT_MAPPING = [
        'order.created' => [
            'main' => [['field' => 'TITLE', 'value' => 'Заказ №#ORDER_ID#'], ['field' => 'COMMENTS', 'value' => '#ORDER_COMMENT#']],
            'contact' => [['field' => 'NAME', 'value' => '#ORDER_NAME#'], ['field' => 'PHONE', 'value' => '#ORDER_PHONE#'], ['field' => 'EMAIL', 'value' => '#ORDER_EMAIL#']],
        ],
        'user_request.created' => [
            'main' => [['field' => 'TITLE', 'value' => 'Заявка №#REQUEST_ID#'], ['field' => 'COMMENTS', 'value' => '#REQUEST_COMMENT#']],
            'contact' => [['field' => 'NAME', 'value' => '#REQUEST_NAME#'], ['field' => 'PHONE', 'value' => '#REQUEST_PHONE#'], ['field' => 'EMAIL', 'value' => '#REQUEST_EMAIL#']],
        ],
        'form_submission.created' => [
            'main' => [['field' => 'TITLE', 'value' => '#FORM_NAME# №#SUBMISSION_ID#'], ['field' => 'COMMENTS', 'value' => '#FORM_FIELDS#']],
            'contact' => [],
        ],
    ];

    /**
     * Events with their variables, and custom forms with their fields, for the rule editor.
     */
    public function meta(): JsonResponse
    {
        $available = MessageEvents::available();
        $events = [];

        foreach (Bitrix24Sync::EVENTS as $code) {
            if (isset($available[$code])) {
                $events[] = [
                    'code' => $code,
                    'label' => $available[$code]['label'],
                    'variables' => MessageEvents::describe(MessageEvents::variablesFor($code)),
                    'default_mapping' => self::DEFAULT_MAPPING[$code],
                ];
            }
        }

        return response()->json(['events' => $events, 'forms' => $this->forms()]);
    }

    public function index(): JsonResponse
    {
        $labels = MessageEvents::all();

        return response()->json([
            'data' => TBitrix24Rule::orderBy('sort')->orderBy('id')->get()->map(fn (TBitrix24Rule $rule) => $rule->toArray() + [
                'event_label' => $labels[$rule->event]['label'] ?? $rule->event,
            ]),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => TBitrix24Rule::findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['data' => TBitrix24Rule::create($this->validated($request))], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rule = TBitrix24Rule::findOrFail($id);
        $rule->update($this->validated($request));

        return response()->json(['data' => $rule]);
    }

    public function destroy(int $id): JsonResponse
    {
        TBitrix24Rule::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    public function toggle(int $id): JsonResponse
    {
        $rule = TBitrix24Rule::findOrFail($id);
        $rule->update(['is_active' => ! $rule->is_active]);

        return response()->json(['is_active' => $rule->is_active]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'event' => ['required', Rule::in(Bitrix24Sync::EVENTS)],
            'form_code' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'entity' => ['required', Rule::in(TBitrix24Rule::ENTITIES)],
            'contact_mode' => ['required', Rule::in(TBitrix24Rule::MODES)],
            'company_mode' => ['required', Rule::in(TBitrix24Rule::MODES)],
            'send_products' => 'boolean',
            'mapping' => 'array',
            'mapping.*' => 'array',
            'mapping.*.*.field' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_]+$/'],
            'mapping.*.*.value' => 'nullable|string|max:5000',
        ], [
            'name.required' => 'Укажите название правила',
            'mapping.*.*.field.required' => 'Выберите поле Битрикс24 в каждой строке сопоставления',
        ]);

        $nothingToCreate = $data['entity'] === TBitrix24Rule::ENTITY_NONE
            && $data['contact_mode'] === TBitrix24Rule::MODE_NONE
            && $data['company_mode'] === TBitrix24Rule::MODE_NONE;

        if ($nothingToCreate) {
            abort(response()->json([
                'message' => 'Правилу нечего создавать',
                'errors' => ['entity' => ['Выберите, что создавать: лид, сделку, контакт или компанию']],
            ], 422));
        }

        $data['form_code'] = $data['event'] === 'form_submission.created' ? ($data['form_code'] ?? null) : null;
        $data['send_products'] = $data['event'] === 'order.created' && ($data['send_products'] ?? false);

        return $data;
    }

    /**
     * @return array<int, array{code: string, name: string, variables: array<int, array<string, mixed>>}>
     */
    private function forms(): array
    {
        if (! app(SiteCacheService::class)->hasTable('t_custom_forms')) {
            return [];
        }

        return TCustomForm::with('fields')->orderBy('name')->get()->map(fn (TCustomForm $form) => [
            'code' => $form->code,
            'name' => $form->name,
            'variables' => MessageEvents::describe(
                $form->fields->mapWithKeys(fn ($field) => ['FIELD_'.strtoupper($field->code) => $field->name])->all()
            ),
        ])->all();
    }
}
