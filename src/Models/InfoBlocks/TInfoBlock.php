<?php

namespace HolartWeb\AxoraCMS\Models\InfoBlocks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TInfoBlock extends Model
{
    protected $table = 't_info_blocks';

    protected $fillable = [
        'code',
        'type',
        'name',
        'description',
        'table_name',
        'is_active',
        'is_favorite',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_favorite' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get the fields for this info block
     */
    public function fields()
    {
        return $this->hasMany(TInfoBlockField::class, 'info_block_id')->orderBy('sort');
    }

    /**
     * Get the elements for this info block
     */
    public function elements()
    {
        return $this->hasMany(TInfoBlockElement::class, 'info_block_id');
    }

    /**
     * Get the sections for this info block
     */
    public function sections()
    {
        return $this->hasMany(TInfoBlockSection::class, 'info_block_id');
    }

    /**
     * Check if this is a catalog type
     */
    public function isCatalog(): bool
    {
        return $this->type === 'catalog';
    }

    /**
     * Check if this is a list type
     */
    public function isList(): bool
    {
        return $this->type === 'list';
    }

    /**
     * Get root sections (sections without parent)
     */
    public function getRootSections()
    {
        return $this->sections()->whereNull('parent_id')->orderBy('sort');
    }

    /**
     * Get sections tree
     */
    public function getSectionsTree(): array
    {
        $rootSections = $this->getRootSections()->get();

        return $rootSections->map(function ($section) {
            return $section->getTree();
        })->toArray();
    }

    /**
     * Generate unique table name for info block
     */
    public static function generateTableName(string $code): string
    {
        return 't_ib_' . Str::snake($code);
    }

    /**
     * Generate unique code from name
     */
    public static function generateCode(string $name): string
    {
        $code = Str::slug($name, '_');

        // Check if code exists
        $originalCode = $code;
        $counter = 1;

        while (static::where('code', $code)->exists()) {
            $code = $originalCode . '_' . $counter;
            $counter++;
        }

        return $code;
    }

    /**
     * Get info block by code (static method for Bitrix-like syntax)
     */
    public static function getByCode(string $code)
    {
        return static::where('code', $code)->where('is_active', true)->first();
    }

    /**
     * Get elements query builder for this info block
     */
    public function getElements()
    {
        return $this->elements()->where('is_active', true)->orderBy('sort');
    }

    /**
     * Get element by ID
     */
    public function getElement(int $id)
    {
        return $this->elements()->where('id', $id)->first();
    }

    /**
     * Get element by code
     */
    public function getElementByCode(string $code)
    {
        return $this->elements()->where('code', $code)->where('is_active', true)->first();
    }

    /**
     * Create new element
     */
    public function createElement(array $data)
    {
        // Generate code if not provided
        if (empty($data['code']) && !empty($data['name'])) {
            $data['code'] = Str::slug($data['name'], '_');
        }

        // Extract properties
        $properties = $data['properties'] ?? [];
        unset($data['properties']);

        // Create element
        $element = $this->elements()->create(array_merge($data, [
            'properties' => $properties
        ]));

        return $element;
    }

    /**
     * Update element
     */
    public function updateElement(int $id, array $data)
    {
        $element = $this->getElement($id);

        if (!$element) {
            return null;
        }

        // Extract properties
        if (isset($data['properties'])) {
            $properties = $data['properties'];
            unset($data['properties']);
            $data['properties'] = $properties;
        }

        $element->update($data);
        return $element->fresh();
    }

    /**
     * Delete element
     */
    public function deleteElement(int $id)
    {
        $element = $this->getElement($id);

        if (!$element) {
            return false;
        }

        return $element->delete();
    }
}
