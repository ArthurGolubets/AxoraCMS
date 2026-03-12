<?php

namespace HolartWeb\HolartCMS\Models\Commerce;

use Illuminate\Database\Eloquent\Model;

class TOrdersData extends Model
{
    protected $table = 't_orders_data';

    protected $fillable = [
        'key',
        'value',
        'type'
    ];

    protected $casts = [
        'value' => 'string',
    ];

    // Константы для type
    const TYPE_STRING = 'string';
    const TYPE_TEXT = 'text';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_INTEGER = 'integer';
    const TYPE_JSON = 'json';

    /**
     * Получить значение с приведением типа
     */
    public function getTypedValue()
    {
        switch ($this->type) {
            case self::TYPE_BOOLEAN:
                return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
            case self::TYPE_INTEGER:
                return (int) $this->value;
            case self::TYPE_JSON:
                return json_decode($this->value, true);
            default:
                return $this->value;
        }
    }

    /**
     * Установить значение с сохранением типа
     */
    public static function setValue(string $key, $value, string $type = self::TYPE_STRING): self
    {
        $serializedValue = $value;

        if ($type === self::TYPE_JSON) {
            $serializedValue = json_encode($value);
        } elseif ($type === self::TYPE_BOOLEAN) {
            $serializedValue = $value ? '1' : '0';
        } else {
            $serializedValue = (string) $value;
        }

        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $serializedValue,
                'type' => $type
            ]
        );
    }

    /**
     * Получить значение по ключу
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return $setting->getTypedValue();
    }
}
