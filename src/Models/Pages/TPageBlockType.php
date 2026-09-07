<?php

namespace HolartWeb\AxoraCMS\Models\Pages;

use Illuminate\Database\Eloquent\Model;

class TPageBlockType extends Model
{
    protected $table = 't_page_block_types';

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'template',
        'fields_schema',
        'preview_image',
        'is_system',
        'is_container',
        'is_active',
        'category',
    ];

    protected $appends = ['identifier'];

    protected $casts = [
        'fields_schema' => 'array',
        'is_system' => 'boolean',
        'is_container' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get identifier attribute (alias for code)
     */
    public function getIdentifierAttribute()
    {
        return $this->code;
    }

    /**
     * Get all blocks using this type
     */
    public function blocks()
    {
        return $this->hasMany(TPageBlock::class, 'block_type_id');
    }

    /**
     * Get block type by code
     */
    public static function getByCode(string $code)
    {
        return static::where('code', $code)->where('is_active', true)->first();
    }

    /**
     * Get all active block types
     */
    public static function getActive()
    {
        return static::where('is_active', true)->orderBy('category')->orderBy('name')->get();
    }

    /**
     * Check if block type can be deleted
     */
    public function canDelete(): bool
    {
        return ! $this->is_system && $this->blocks()->count() === 0;
    }
}
