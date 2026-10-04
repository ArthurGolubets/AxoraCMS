<?php

namespace HolartWeb\AxoraCMS\Models\InfoBlocks;

use Illuminate\Database\Eloquent\Model;

class TInfoBlockElement extends Model
{
    protected $table = 't_info_block_elements';

    protected $fillable = [
        'info_block_id',
        'section_id',
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
     * Get the section that owns this element
     */
    public function section()
    {
        return $this->belongsTo(TInfoBlockSection::class, 'section_id');
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

        if (! $infoBlock) {
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
     * Whether the key is a real table column rather than an info block property.
     */
    protected function isModelColumn(string $key): bool
    {
        return in_array($key, $this->fillable, true)
            || in_array($key, [$this->getKeyName(), $this->getCreatedAtColumn(), $this->getUpdatedAtColumn()], true);
    }

    /**
     * Magic getter for properties
     */
    public function __get($key)
    {
        // First try to get from model attributes / relations
        if (array_key_exists($key, $this->attributes) || $this->hasGetMutator($key) || $this->isRelation($key)) {
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
        // If it's a model attribute (incl. timestamps not yet set on a new model), set it
        if (array_key_exists($key, $this->attributes) || $this->isModelColumn($key) || $this->hasSetMutator($key)) {
            parent::__set($key, $value);

            return;
        }

        // Otherwise set it as a property
        $this->setProperty($key, $value);
    }
}
