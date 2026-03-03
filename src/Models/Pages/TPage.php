<?php

namespace HolartWeb\HolartCMS\Models\Pages;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TPage extends Model
{
    protected $table = 't_pages';

    protected $fillable = [
        'title',
        'slug',
        'type',
        'content',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'is_active',
        'sort',
        'header_template',
        'footer_template',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    const TYPE_STATIC = 'static';
    const TYPE_DYNAMIC = 'dynamic';

    /**
     * Get all blocks for this page
     */
    public function blocks()
    {
        return $this->hasMany(TPageBlock::class, 'page_id')->orderBy('sort');
    }

    /**
     * Generate unique slug from title
     */
    public static function generateSlug(string $title, ?int $excludeId = null): string
    {
        $slug = self::transliterate($title);
        $slug = Str::slug($slug, '_');

        // Check if slug exists
        $originalSlug = $slug;
        $counter = 1;

        $query = static::where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '_' . $counter;
            $counter++;
            $query = static::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }

    /**
     * Transliterate Russian to Latin
     */
    protected static function transliterate(string $text): string
    {
        $ru = ['а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я'];
        $en = ['a','b','v','g','d','e','yo','zh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','h','ts','ch','sh','sch','','y','','e','yu','ya'];

        return str_replace($ru, $en, mb_strtolower($text));
    }

    /**
     * Get page by slug
     */
    public static function getBySlug(string $slug)
    {
        return static::where('slug', $slug)->where('is_active', true)->first();
    }

    /**
     * Duplicate page
     */
    public function duplicate(): self
    {
        $newPage = $this->replicate();
        $newPage->title = $this->title . ' (копия)';
        $newPage->slug = self::generateSlug($newPage->title);
        $newPage->save();

        // Duplicate blocks if dynamic
        if ($this->type === self::TYPE_DYNAMIC) {
            foreach ($this->blocks as $block) {
                $newBlock = $block->replicate();
                $newBlock->page_id = $newPage->id;
                $newBlock->save();
            }
        }

        return $newPage;
    }
}
