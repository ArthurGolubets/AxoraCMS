<?php

namespace HolartWeb\HolartCMS\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use HolartWeb\HolartCMS\Models\TAdminAction;

class ImportCatalogsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 1;

    protected $items;
    protected $importId;

    /**
     * Create a new job instance.
     */
    public function __construct($items, $importId)
    {
        $this->items = $items;
        $this->importId = $importId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        if (!class_exists('App\Models\TCatalog')) {
            $this->updateProgress(0, 0, count($this->items), ['Catalog module not available'], 'error');
            return;
        }

        $catalogClass = 'App\Models\TCatalog';
        $created = 0;
        $updated = 0;
        $errors = [];
        $total = count($this->items);
        $processed = 0;

        foreach ($this->items as $item) {
            try {
                // Skip items with errors
                if (isset($item['status']) && $item['status'] === 'error') {
                    $processed++;
                    continue;
                }

                // Auto-generate slug if empty
                if (empty($item['slug'])) {
                    $item['slug'] = $this->generateSlug($item['name'], $catalogClass, $item['id'] ?? null);
                }

                // Download and save image if URL is provided
                $imagePath = null;
                if (!empty($item['image']) && filter_var($item['image'], FILTER_VALIDATE_URL)) {
                    $imagePath = $this->downloadImage($item['image'], 'catalogs');
                } elseif (!empty($item['image'])) {
                    $imagePath = $item['image'];
                }

                $data = [
                    'name' => $item['name'],
                    'slug' => $item['slug'],
                    'description' => $item['description'] ?? null,
                    'parent_id' => !empty($item['parent_id']) ? $item['parent_id'] : null,
                    'is_active' => $item['is_active'] ?? true,
                    'image' => $imagePath,
                ];

                if ($item['action'] === 'update' && !empty($item['id'])) {
                    $catalog = $catalogClass::find($item['id']);
                    if ($catalog) {
                        $catalog->update($data);
                        $updated++;
                    }
                } else {
                    $catalogClass::create($data);
                    $created++;
                }

                $processed++;

                // Update progress every 10 items or on last item
                if ($processed % 10 === 0 || $processed === $total) {
                    $this->updateProgress($created, $updated, $total, $errors, 'processing', $processed);
                }

            } catch (\Exception $e) {
                $errors[] = "Ошибка в строке {$item['row']}: " . $e->getMessage();
                $processed++;
            }
        }

        // Log activity
        TAdminAction::log('imported', 'catalog', null,
            'Импорт категорий (создано: ' . $created . ', обновлено: ' . $updated . ')');

        // Final update
        $this->updateProgress($created, $updated, $total, $errors, 'completed', $processed);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        $this->updateProgress(0, 0, count($this->items), [$exception->getMessage()], 'failed');
    }

    /**
     * Update progress in cache
     */
    private function updateProgress($created, $updated, $total, $errors, $status, $processed = 0)
    {
        Cache::put("import_progress_{$this->importId}", [
            'status' => $status,
            'created' => $created,
            'updated' => $updated,
            'total' => $total,
            'processed' => $processed,
            'errors' => $errors,
            'percentage' => $total > 0 ? round(($processed / $total) * 100) : 0,
        ], 3600); // Cache for 1 hour
    }

    /**
     * Generate unique slug
     */
    private function generateSlug($name, $modelClass, $excludeId = null)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = $modelClass::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Download image from URL and save to storage
     */
    private function downloadImage($url, $folder = 'catalogs')
    {
        try {
            // Get image content
            $imageContent = @file_get_contents($url);
            if ($imageContent === false) {
                return null;
            }

            // Get file extension from URL or content type
            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = 'jpg';
            }

            // Generate unique filename
            $filename = time() . '_' . Str::random(10) . '.' . $extension;
            $path = $folder . '/' . $filename;

            // Save to storage
            Storage::disk('public')->put($path, $imageContent);

            return '/storage/' . $path;
        } catch (\Exception $e) {
            \Log::error('Failed to download image: ' . $e->getMessage());
            return null;
        }
    }
}
