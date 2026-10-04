<?php

namespace HolartWeb\AxoraCMS\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An admin-defined custom project field ("Пользовательское свойство").
 */
class TPanelCustomField extends Model
{
    protected $table = 't_panel_custom_fields';

    protected $fillable = [
        'code',
        'name',
        'type',
        'is_multiple',
        'sort',
        'value',
        'default_value',
    ];

    protected $casts = [
        'is_multiple' => 'boolean',
        'sort' => 'integer',
        'value' => 'json',
        'default_value' => 'json',
    ];

    /**
     * Field types that support the "multiple" flag.
     */
    public const MULTIPLE_CAPABLE_TYPES = ['text', 'html', 'number', 'image', 'file', 'email', 'phone'];

    /**
     * All supported field types.
     */
    public const TYPES = ['text', 'html', 'number', 'image', 'file', 'email', 'phone', 'table', 'boolean'];

    /**
     * The value to output: the stored value, or the default while it is empty.
     */
    public function resolvedValue(): mixed
    {
        $isEmpty = $this->value === null || $this->value === '' || $this->value === [];

        return $isEmpty ? $this->default_value : $this->value;
    }

    /**
     * All custom fields as a [code => value] map for projectSettings.
     *
     * @return array<string, mixed>
     */
    public static function asSettingsMap(): array
    {
        return static::orderBy('sort')->orderBy('id')->get()
            ->mapWithKeys(fn (self $field) => [$field->code => $field->resolvedValue()])
            ->all();
    }
}
