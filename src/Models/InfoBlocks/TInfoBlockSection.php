<?php

namespace HolartWeb\AxoraCMS\Models\InfoBlocks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TInfoBlockSection extends Model
{
    protected $table = 't_info_block_sections';

    protected $fillable = [
        'info_block_id',
        'parent_id',
        'name',
        'code',
        'image',
        'description',
        'sort',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the info block that owns this section
     */
    public function infoBlock()
    {
        return $this->belongsTo(TInfoBlock::class, 'info_block_id');
    }

    /**
     * Get parent section
     */
    public function parent()
    {
        return $this->belongsTo(TInfoBlockSection::class, 'parent_id');
    }

    /**
     * Get child sections
     */
    public function children()
    {
        return $this->hasMany(TInfoBlockSection::class, 'parent_id')->orderBy('sort');
    }

    /**
     * Get elements in this section
     */
    public function elements()
    {
        return $this->hasMany(TInfoBlockElement::class, 'section_id');
    }

    /**
     * Get all descendants (recursive)
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Generate unique code from name
     */
    public static function generateCode(string $name, int $infoBlockId): string
    {
        $code = Str::slug($name, '_');

        // Check if code exists in this info block
        $originalCode = $code;
        $counter = 1;

        while (static::where('info_block_id', $infoBlockId)->where('code', $code)->exists()) {
            $code = $originalCode . '_' . $counter;
            $counter++;
        }

        return $code;
    }

    /**
     * Get section by code
     */
    public static function getByCode(int $infoBlockId, string $code)
    {
        return static::where('info_block_id', $infoBlockId)
            ->where('code', $code)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get breadcrumbs (path from root to this section)
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        $current = $this;

        while ($current) {
            array_unshift($breadcrumbs, [
                'id' => $current->id,
                'name' => $current->name,
                'code' => $current->code,
            ]);
            $current = $current->parent;
        }

        return $breadcrumbs;
    }

    /**
     * Get tree structure starting from this section
     */
    public function getTree(): array
    {
        // Get child sections (folders first)
        $childSections = $this->children->map(function ($child) {
            return $child->getTree();
        })->toArray();

        // Get elements in this section
        $sectionElements = $this->elements()
            ->orderBy('sort')
            ->get(['id', 'name', 'code', 'is_active'])
            ->toArray();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'image' => $this->image,
            'description' => $this->description,
            'sort' => $this->sort,
            'is_active' => $this->is_active,
            'children' => $childSections,
            'elements' => $sectionElements,
        ];
    }

    /**
     * Check if this section is ancestor of another section
     */
    public function isAncestorOf(TInfoBlockSection $section): bool
    {
        $parent = $section->parent;

        while ($parent) {
            if ($parent->id === $this->id) {
                return true;
            }
            $parent = $parent->parent;
        }

        return false;
    }

    /**
     * Check if this section is descendant of another section
     */
    public function isDescendantOf(TInfoBlockSection $section): bool
    {
        return $section->isAncestorOf($this);
    }
}
