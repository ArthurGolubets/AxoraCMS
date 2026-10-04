<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Callback;

use HolartWeb\AxoraCMS\Models\Callback\TCustomForm;
use HolartWeb\AxoraCMS\Models\Callback\TCustomFormField;
use HolartWeb\AxoraCMS\Models\TAdminAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Admin CRUD for custom forms ("Своя форма") and their fields.
 */
class CustomFormsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $forms = TCustomForm::query()
            ->withCount([
                'fields',
                'submissions',
                'submissions as new_submissions_count' => fn ($query) => $query->whereNull('viewed_at'),
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->get('search').'%';
                $query->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('code', 'like', $search));
            })
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return response()->json($forms);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(TCustomForm::with('fields')->findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateForm($request);

        $form = DB::transaction(function () use ($validated) {
            $form = TCustomForm::create($validated['form']);
            $this->syncFields($form, $validated['fields']);

            return $form;
        });

        TAdminAction::log('created', 'custom_form', $form->id, 'Создана форма "'.$form->name.'"');

        return response()->json($form->load('fields'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $form = TCustomForm::findOrFail($id);
        $validated = $this->validateForm($request, $form);

        DB::transaction(function () use ($form, $validated) {
            $form->update($validated['form']);
            $this->syncFields($form, $validated['fields']);
        });

        TAdminAction::log('updated', 'custom_form', $form->id, 'Обновлена форма "'.$form->name.'"');

        return response()->json($form->fresh('fields'));
    }

    public function destroy(int $id): JsonResponse
    {
        $form = TCustomForm::findOrFail($id);
        $name = $form->name;
        $form->delete();

        TAdminAction::log('deleted', 'custom_form', $id, 'Удалена форма "'.$name.'"');

        return response()->json(['message' => 'Форма удалена']);
    }

    /**
     * @return array{form: array<string, mixed>, fields: array<int, array<string, mixed>>}
     *
     * @throws ValidationException
     */
    protected function validateForm(Request $request, ?TCustomForm $form = null): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/', Rule::unique('t_custom_forms', 'code')->ignore($form?->id)],
            'description' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
            'admin_can_create' => 'boolean',
            'notify_admin' => 'boolean',
            'success_message' => 'nullable|string|max:255',
            'sort' => 'nullable|integer|min:0',
            'fields' => 'present|array',
            'fields.*.id' => 'nullable|integer',
            'fields.*.name' => 'required|string|max:255',
            'fields.*.code' => 'required|string|max:100|regex:/^[a-z0-9_]+$/',
            'fields.*.type' => ['required', Rule::in(array_keys(TCustomFormField::TYPES))],
            'fields.*.is_required' => 'boolean',
            'fields.*.is_multiple' => 'boolean',
            'fields.*.settings' => 'nullable|array',
        ], [], [
            'fields.*.name' => 'название поля',
            'fields.*.code' => 'код поля',
        ]);

        $codes = array_column($validated['fields'], 'code');
        if (count($codes) !== count(array_unique($codes))) {
            throw ValidationException::withMessages(['fields' => 'Коды полей формы должны быть уникальными']);
        }

        $fields = $validated['fields'];
        unset($validated['fields']);

        return ['form' => $validated, 'fields' => $fields];
    }

    /**
     * Create / update the submitted fields and delete the missing ones.
     *
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function syncFields(TCustomForm $form, array $fields): void
    {
        $submittedIds = array_values(array_filter(array_map(fn ($field) => $field['id'] ?? null, $fields)));

        $form->fields()->whereNotIn('id', $submittedIds ?: [0])->delete();

        // Codes are unique per form: park the kept fields on temporary codes so
        // that fields may swap or reuse codes within one save.
        foreach ($form->fields()->get() as $existing) {
            $existing->update(['code' => '__tmp_'.$existing->id]);
        }

        foreach (array_values($fields) as $index => $field) {
            $type = $field['type'];
            $attributes = [
                'name' => $field['name'],
                'code' => $field['code'],
                'type' => $type,
                'is_required' => (bool) ($field['is_required'] ?? false),
                'is_multiple' => ! in_array($type, TCustomFormField::SINGLE_ONLY_TYPES, true) && (bool) ($field['is_multiple'] ?? false),
                'sort' => ($index + 1) * 10,
                'settings' => $field['settings'] ?? null,
            ];

            $model = ! empty($field['id']) ? $form->fields()->whereKey($field['id'])->first() : null;

            $model ? $model->update($attributes) : $form->fields()->create($attributes);
        }
    }
}
