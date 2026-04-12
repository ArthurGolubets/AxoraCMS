<?php

namespace HolartWeb\AxoraCMS\Models\InfoBlocks;

use Illuminate\Database\Eloquent\Model;

class TInfoBlockElement extends Model
{
    protected $table = 't_info_block_elements';

    protected $fillable = [
        'info_block_id',
        'name',
        'code',
        'content',
        'is_active',
        'sort',
        'properties',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'properties' => 'array',
    ];

    /**
     * Get the info block that owns this element
     */
    public function infoBlock()
    {
        return $this->belongsTo(TInfoBlock::class, 'info_block_id');
    }

    /**
     * Get property value
     */
    public function getProperty(string $code, $default = null)
    {
        return $this->properties[$code] ?? $default;
    }

    /**
     * Set property value
     */
    public function setProperty(string $code, $value): void
    {
        $properties = $this->properties ?? [];
        $properties[$code] = $value;
        $this->properties = $properties;
    }

    /**
     * Get all properties with field info
     */
    public function getPropertiesWithFields()
    {
        $infoBlock = $this->infoBlock()->with('fields')->first();

        if (!$infoBlock) {
            return [];
        }

        $result = [];

        foreach ($infoBlock->fields as $field) {
            $result[$field->code] = [
                'field' => $field,
                'value' => $this->getProperty($field->code),
            ];
        }

        return $result;
    }

    /**
     * Magic getter for properties
     */
    public function __get($key)
    {
        // First try to get from model attributes
        if (array_key_exists($key, $this->attributes) || $this->hasGetMutator($key)) {
            return parent::__get($key);
        }

        // Then try to get from properties
        return $this->getProperty($key);
    }

    /**
     * Magic setter for properties
     */
    public function __set($key, $value)
    {
        // If it's a model attribute, set it
        if (array_key_exists($key, $this->attributes)) {
            parent::__set($key, $value);
            return;
        }

        // Otherwise set it as a property
        $this->setProperty($key, $value);
    }
}
