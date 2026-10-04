<?php

namespace HolartWeb\AxoraCMS\Models\Menus;

use Illuminate\Database\Eloquent\Model;

class TMenuItem extends Model
{
    protected $table = 't_menu_items';

    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'url',
        'route',
        'target',
        'sort',
        'is_active',
        'extra_attributes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'extra_attributes' => 'array',
    ];

    const TARGET_SELF = '_self';

    const TARGET_BLANK = '_blank';

    /**
     * Sort value that places a new item after its siblings.
     */
    public static function nextSort(int $menuId, ?int $parentId = null): int
    {
        $max = static::where('menu_id', $menuId)
            ->when($parentId, fn ($query) => $query->where('parent_id', $parentId), fn ($query) => $query->whereNull('parent_id'))
            ->max('sort');

        return $max === null ? 10 : ((int) $max + 10);
    }

    /**
     * Get the menu this item belongs to
     */
    public function menu()
    {
        return $this->belongsTo(TMenu::class, 'menu_id');
    }

    /**
     * Get parent menu item
     */
    public function parent()
    {
        return $this->belongsTo(TMenuItem::class, 'parent_id');
    }

    /**
     * Get child menu items
     */
    public function children()
    {
        return $this->hasMany(TMenuItem::class, 'parent_id')->orderBy('sort');
    }

    /**
     * Check if item has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get the final URL for the menu item
     */
    public function getUrlAttribute($value)
    {
        // If route is set, use it
        if ($this->route) {
            return route($this->route);
        }

        // Otherwise return the URL value
        return $value;
    }
}
