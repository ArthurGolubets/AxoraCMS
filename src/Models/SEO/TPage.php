<?php

namespace HolartWeb\AxoraCMS\Models\SEO;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TPage extends Model
{
    protected $table = 't_pages';

    protected $fillable = [
        'title',
        'slug',
        'type',
        'route_name',
        'content',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'is_active',
        'views_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'views_count' => 'integer',
    ];

    /**
     * Relationship: page visits
     */
    public function visits(): HasMany
    {
        return $this->hasMany(TPageVisit::class, 'page_id');
    }

    /**
     * Generate unique slug from title
     */
    public static function generateSlug(string $title, ?int $id = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = static::where('slug', $slug);

            if ($id) {
                $query->where('id', '!=', $id);
            }

            if (! $query->exists()) {
                break;
            }

            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Increment views count (cached)
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Get views count for specific period
     */
    public function getViewsForPeriod(string $period = '7days'): int
    {
        $date = match ($period) {
            'today' => now()->startOfDay(),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            'year' => now()->subYear(),
            default => now()->subDays(7),
        };

        return $this->visits()->where('visited_at', '>=', $date)->count();
    }

    /**
     * Scope: only active pages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: only static pages
     */
    public function scopeStatic($query)
    {
        return $query->where('type', 'static');
    }

    /**
     * Scope: only dynamic pages
     */
    public function scopeDynamic($query)
    {
        return $query->where('type', 'dynamic');
    }
}
