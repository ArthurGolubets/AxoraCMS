<?php

namespace HolartWeb\HolartCMS\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CleanOldPageVisitsCommand extends Command
{
    protected $signature = 'holartcms:clean-page-visits {--days=30 : Number of days to keep}';
    protected $description = 'Clean page visits older than specified days';

    public function handle(): int
    {
        if (!Schema::hasTable('t_page_visits')) {
            $this->warn('Table t_page_visits does not exist. Skipping cleanup.');
            return Command::FAILURE;
        }

        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info("Cleaning page visits older than {$days} days (before {$cutoffDate->toDateString()})...");

        // Get model class
        $pageVisitModel = $this->getPageVisitModel();

        if (!$pageVisitModel) {
            $this->error('TPageVisit model not found.');
            return Command::FAILURE;
        }

        // Count records to delete
        $count = $pageVisitModel::where('visited_at', '<', $cutoffDate)->count();

        if ($count === 0) {
            $this->info('No old records to clean.');
            return Command::SUCCESS;
        }

        // Delete old records
        $deleted = $pageVisitModel::where('visited_at', '<', $cutoffDate)->delete();

        $this->info("✓ Deleted {$deleted} old page visit records.");

        return Command::SUCCESS;
    }

    /**
     * Get PageVisit model class
     */
    private function getPageVisitModel(): ?string
    {
        // Use package model
        if (class_exists('HolartWeb\HolartCMS\Models\SEO\TPageVisit')) {
            return 'HolartWeb\HolartCMS\Models\SEO\TPageVisit';
        }

        return null;
    }
}
