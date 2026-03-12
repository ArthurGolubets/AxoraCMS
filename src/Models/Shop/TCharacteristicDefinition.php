<?php

namespace HolartWeb\HolartCMS\Models\Shop;

use Illuminate\Database\Eloquent\Model;

class TCharacteristicDefinition extends Model
{
    protected $table = 't_characteristic_definitions';

    protected $fillable = [
        'name',
        'code',
        'type',
        'multiple',
        'applies_to',
        'sort_order',
    ];

    protected $casts = [
        'multiple' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get definitions for catalogs
     */
    public static function forCatalog()
    {
        return static::whereIn('applies_to', ['catalog', 'both'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get definitions for products
     */
    public static function forProduct()
    {
        return static::whereIn('applies_to', ['product', 'both'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all definitions ordered by sort_order
     */
    public static function getAllOrdered()
    {
        return static::orderBy('sort_order')->orderBy('name')->get();
    }

    /**
     * Generate unique code from name
     */
    public static function generateCode(string $name): string
    {
        $translitMap = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo',
            'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
            'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '',
            'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo',
            'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M',
            'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U',
            'Ф' => 'F', 'Х' => 'H', 'Ц' => 'Ts', 'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch', 'Ъ' => '',
            'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya'
        ];

        $code = mb_convert_case($name, MB_CASE_LOWER);
        $code = strtr($code, $translitMap);
        $code = preg_replace('/[^a-z0-9]+/', '_', $code);
        $code = trim($code, '_');

        // Ensure unique code
        $originalCode = $code;
        $count = 1;

        while (static::where('code', $code)->exists()) {
            $code = $originalCode . '_' . $count;
            $count++;
        }

        return $code;
    }
}
