<?php

namespace HolartWeb\AxoraCMS\Console;

use HolartWeb\AxoraCMS\Models\SEO\TPage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class ScanRoutesCommand extends Command
{
    protected $signature = 'axoracms:scan-routes';

    protected $description = 'Scan application routes and create/update static pages';

    private $created = 0;

    private $updated = 0;

    private $skipped = 0;

    private $foundRoutes = [];

    public static $lastResults = null;

    public function handle(): int
    {
        $this->info('╔═════════════════════════════════════════╗');
        $this->info('║   AxoraCMS Route Scanner              ║');
        $this->info('╚═════════════════════════════════════════╝');
        $this->newLine();

        $routes = $this->getWebRoutes();

        $this->info('Found '.count($routes).' web routes');
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($routes));
        $progressBar->start();

        foreach ($routes as $route) {
            $routeName = $route['name'];
            $uri = $route['uri'];

            // Skip routes with parameters
            if (str_contains($uri, '{')) {
                $this->skipped++;
                $this->foundRoutes[] = [
                    'uri' => $uri,
                    'name' => $routeName,
                    'status' => 'skipped',
                    'reason' => 'Содержит параметры',
                ];
                $progressBar->advance();

                continue;
            }

            // Generate slug from URI
            $slug = $uri === '/' ? 'home' : trim($uri, '/');

            // If no slug (empty), skip
            if (empty($slug)) {
                $slug = 'home';
            }

            // Check if page already exists by slug or route_name
            $existingPage = TPage::where('slug', $slug)
                ->orWhere(function ($q) use ($routeName) {
                    if ($routeName) {
                        $q->where('route_name', $routeName);
                    }
                })
                ->first();

            if ($existingPage) {
                // Always update existing pages to sync route names
                $existingPage->update([
                    'route_name' => $routeName,
                    'type' => 'static', // Ensure scanned routes are marked as static
                ]);
                $this->updated++;
                $this->foundRoutes[] = [
                    'uri' => $uri,
                    'name' => $routeName,
                    'status' => 'updated',
                    'page_id' => $existingPage->id,
                ];
            } else {
                // Create new static page (scanned routes are static with route_name)
                $page = TPage::create([
                    'title' => $routeName ? $this->generateTitleFromRoute($routeName) : ucfirst(str_replace(['/', '-'], ' ', $slug)),
                    'slug' => TPage::generateSlug($slug),
                    'type' => 'static',
                    'route_name' => $routeName,
                    'is_active' => true,
                ]);
                $this->created++;
                $this->foundRoutes[] = [
                    'uri' => $uri,
                    'name' => $routeName,
                    'status' => 'created',
                    'page_id' => $page->id,
                ];
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('╔═════════════════════════════════════════╗');
        $this->info('║   Scan completed successfully          ║');
        $this->info('╚═════════════════════════════════════════╝');
        $this->newLine();

        $this->table(
            ['Status', 'Count'],
            [
                ['Created', $this->created],
                ['Updated', $this->updated],
                ['Skipped', $this->skipped],
            ]
        );

        // Store results for later retrieval
        self::$lastResults = [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'routes' => $this->foundRoutes,
        ];

        return Command::SUCCESS;
    }

    /**
     * Get scan results
     */
    public function getResults(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'routes' => $this->foundRoutes,
        ];
    }

    /**
     * Get last scan results (static)
     */
    public static function getLastResults(): ?array
    {
        return self::$lastResults;
    }

    /**
     * Get all web routes
     */
    private function getWebRoutes(): array
    {
        $routes = [];
        $allRoutes = Route::getRoutes();

        $this->info('Total routes found: '.count($allRoutes));

        foreach ($allRoutes as $route) {
            $middleware = $route->gatherMiddleware();
            $uri = $route->uri();
            $methods = $route->methods();
            $name = $route->getName();

            // Debug: show all web routes
            if (in_array('web', $middleware)) {
                $this->line("  - [{$methods[0]}] {$uri} (name: {$name}, middleware: ".implode(',', $middleware).')');
            }

            // Only GET routes from web middleware, excluding admin routes
            if (
                in_array('GET', $methods) &&
                in_array('web', $middleware) &&
                ! str_starts_with($uri, 'admin') &&
                ! str_starts_with($uri, 'admin/')
            ) {
                $routes[] = [
                    'name' => $name,
                    'uri' => $uri,
                    'methods' => $methods,
                ];
            }
        }

        return $routes;
    }

    /**
     * Generate title from route name
     */
    private function generateTitleFromRoute(string $routeName): string
    {
        // Convert 'home.index' to 'Home Index'
        $title = str_replace(['.', '-', '_'], ' ', $routeName);

        return ucwords($title);
    }
}
