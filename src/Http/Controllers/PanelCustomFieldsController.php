<?php

namespace HolartWeb\AxoraCMS\Http\Controllers;

use HolartWeb\AxoraCMS\Models\TAdminAction;
use HolartWeb\AxoraCMS\Models\TPanelCustomField;
use HolartWeb\AxoraCMS\Support\HtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

/**
 * Manage admin-defined custom project fields ("Пользовательские свойства").
 */
class PanelCustomFieldsController extends Controller
{
    /**
     * List every custom field with its current value.
     */
    public function index(): JsonResponse
    {
        return response()->json(
            TPanelCustomField::orderBy('sort')->orderBy('id')->get()
        );
    }

    /**
     * Replace the whole set of custom fields (create / update / delete-missing).
     */
    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fields' => 'present|array',
            'fields.*.id' => 'nullable|integer',
            'fields.*.code' => 'nullable|string|max:100|regex:/^[a-z0-9_]+$/i',
            'fields.*.name' => 'required|string|max:255',
            'fields.*.type' => ['required', 'string', 'in:'.implode(',', TPanelCustomField::TYPES)],
            'fields.*.is_multiple' => 'boolean',
            'fields.*.sort' => 'nullable|integer',
            'fields.*.value' => 'nullable',
        ]);

        $keptIds = [];
        $usedCodes = [];

        foreach ($validated['fields'] as $index => $field) {
            $type = $field['type'];
            $isMultiple = ($type === 'table') ? false : (bool) ($field['is_multiple'] ?? false);

            $code = $this->normalizeCode($field['code'] ?? '', $field['name'], $usedCodes);
            $usedCodes[] = $code;

            $attributes = [
                'code' => $code,
                'name' => $field['name'],
                'type' => $type,
                'is_multiple' => $isMultiple,
                'sort' => $field['sort'] ?? (($index + 1) * 10),
                'value' => $this->normalizeValue($type, $isMultiple, $field['value'] ?? null),
            ];

            $model = ! empty($field['id'])
                ? TPanelCustomField::find($field['id'])
                : null;

            if ($model) {
                $model->update($attributes);
            } else {
                $model = TPanelCustomField::create($attributes);
            }

            $keptIds[] = $model->id;
        }

        TPanelCustomField::whereNotIn('id', $keptIds ?: [0])->delete();

        TAdminAction::log('changed', 'setting', null, 'Изменены пользовательские свойства');

        return response()->json([
            'message' => 'Пользовательские свойства сохранены',
            'fields' => TPanelCustomField::orderBy('sort')->orderBy('id')->get(),
        ]);
    }

    /**
     * Build a stable, unique snake_case code.
     *
     * @param  array<int, string>  $used
     */
    protected function normalizeCode(string $code, string $name, array $used): string
    {
        $code = strtolower(trim($code));
        $code = preg_replace('/[^a-z0-9_]+/', '_', $code) ?? '';
        $code = trim($code, '_');

        if ($code === '') {
            $code = Str::slug($name, '_') ?: 'field';
        }

        $base = $code;
        $n = 1;
        while (in_array($code, $used, true)) {
            $n++;
            $code = $base.'_'.$n;
        }

        return $code;
    }

    /**
     * Coerce a field value to the shape implied by its type / multiplicity,
     * and sanitize HTML.
     */
    protected function normalizeValue(string $type, bool $isMultiple, mixed $value): mixed
    {
        $clean = function ($single) use ($type) {
            if ($single === null) {
                return null;
            }

            return match ($type) {
                'html' => HtmlSanitizer::clean((string) $single),
                'number' => is_numeric($single) ? $single + 0 : null,
                'table' => is_array($single) ? $single : [],
                default => is_scalar($single) ? (string) $single : null,
            };
        };

        if ($type === 'table') {
            return is_array($value) ? $value : [];
        }

        if ($isMultiple) {
            $items = is_array($value) ? $value : ($value === null || $value === '' ? [] : [$value]);
            $items = array_map($clean, $items);
            $items = array_values(array_filter($items, fn ($v) => $v !== null && $v !== ''));

            return $items;
        }

        return $clean(is_array($value) ? ($value[0] ?? null) : $value);
    }
}
