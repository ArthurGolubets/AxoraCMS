<?php

namespace HolartWeb\AxoraCMS\Models\InfoBlocks;

use Illuminate\Database\Eloquent\Model;

class TInfoBlockField extends Model
{
    protected $table = 't_info_block_fields';

    protected $fillable = [
        'info_block_id',
        'code',
        'name',
        'type',
        'sort',
        'is_required',
        'is_multiple',
        'settings',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_multiple' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get the info block that owns this field
     */
    public function infoBlock()
    {
        return $this->belongsTo(TInfoBlock::class, 'info_block_id');
    }

    /**
     * Available field types
     */
    public static function getAvailableTypes(): array
    {
        return [
            'string' => 'Строка',
            'text' => 'Текст',
            'number' => 'Число',
            'double' => 'Число с плавающей точкой',
            'bool' => 'Да/Нет',
            'date' => 'Дата',
            'datetime' => 'Дата и время',
            'image' => 'Изображение',
            'file' => 'Файл',
            'entity' => 'Привязка к элементу',
            'user' => 'Привязка к пользователю',
            'enum' => 'Список',
        ];
    }

    /**
     * Validate field value
     */
    public function validateValue($value): bool
    {
        // Required check
        if ($this->is_required && empty($value)) {
            return false;
        }

        // Type validation
        switch ($this->type) {
            case 'number':
                return is_numeric($value);
            case 'double':
                return is_numeric($value);
            case 'bool':
                return is_bool($value) || in_array($value, [0, 1, '0', '1', true, false]);
            case 'date':
            case 'datetime':
                return strtotime($value) !== false;
            case 'enum':
                $options = $this->settings['options'] ?? [];
                $codes = array_column($options, 'code');
                return in_array($value, $codes);
            default:
                return true;
        }
    }

    /**
     * Cast value to appropriate type
     */
    public function castValue($value)
    {
        if ($value === null) {
            return null;
        }

        switch ($this->type) {
            case 'number':
                return (int) $value;
            case 'double':
                return (float) $value;
            case 'bool':
                return (bool) $value;
            case 'string':
            case 'text':
            case 'image':
            case 'enum':
            case 'file':
                return (string) $value;
            default:
                return $value;
        }
    }
    public function getEnumTitle($code): ?string
    {
        $options = $this->settings['options'] ?? [];
        foreach ($options as $option) {
            if ($option['code'] === $code) {
                return $option['title'];
            }
        }
        return null;
    }

}
