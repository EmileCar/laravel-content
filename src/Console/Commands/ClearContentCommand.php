<?php

namespace Carone\Content\Console\Commands;

use Carone\Content\Models\PageContent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearContentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'content:clear
                            {--page= : Clear content for a specific page only}
                            {--locale= : Clear content for a specific locale only}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear page content from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pageId = $this->option('page');
        $locale = $this->option('locale');

        if ($pageId || $locale) {
            return $this->clearFilteredContent($pageId, $locale);
        }

        return $this->clearAllContent();
    }

    /**
     * Clear all content
     */
    protected function clearAllContent()
    {
        $count = PageContent::count();

        if ($count === 0) {
            $this->info('No content to clear.');
            return Command::SUCCESS;
        }

        $this->warn("⚠️  This will delete ALL {$count} content entries!");
        $this->newLine();

        if (!$this->option('force') && !$this->confirm('Are you sure you want to continue?', false)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        // Get all unique page IDs and locales before deleting
        $items = PageContent::select('page_id', 'locale')->distinct()->get();

        // Delete all content
        PageContent::truncate();

        // Clear all caches
        foreach ($items as $item) {
            $this->clearCache($item->page_id, $item->locale);
        }

        $this->newLine();
        $this->info("✅ Successfully deleted {$count} content entries!");
        $this->info('✅ Cache cleared for all pages and locales.');

        return Command::SUCCESS;
    }

    /**
     * Clear filtered content
     */
    protected function clearFilteredContent($pageId = null, $locale = null)
    {
        $query = PageContent::query();

        if ($pageId) {
            $query->where('page_id', $pageId);
        }

        if ($locale) {
            $query->where('locale', $locale);
        }

        $count = $query->count();

        if ($count === 0) {
            $filters = [];
            if ($pageId) $filters[] = "page '{$pageId}'";
            if ($locale) $filters[] = "locale '{$locale}'";
            $this->info("No content found for " . implode(' and ', $filters) . ".");
            return Command::SUCCESS;
        }

        $filters = [];
        if ($pageId) $filters[] = "page '{$pageId}'";
        if ($locale) $filters[] = "locale '{$locale}'";

        $this->warn("⚠️  This will delete {$count} content entries for " . implode(' and ', $filters) . "!");
        $this->newLine();

        if (!$this->option('force') && !$this->confirm('Are you sure you want to continue?', false)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        // Show what will be deleted
        $contents = $query->get();

        $this->table(
            ['Element ID', 'Locale', 'Type', 'Value'],
            $contents->map(fn($c) => [
                $c->element_id,
                $c->locale,
                $c->type,
                $this->truncate($c->value)
            ])
        );

        // Get unique page/locale combinations before deleting
        $items = $contents->unique(function ($item) {
            return $item->page_id . '-' . $item->locale;
        });

        // Delete content
        $query->delete();

        // Clear cache
        foreach ($items as $item) {
            $this->clearCache($item->page_id, $item->locale);
        }

        $this->newLine();
        $filterText = implode(' and ', $filters);
        $this->info("✅ Successfully deleted {$count} content entries for {$filterText}!");
        $this->info('✅ Cache cleared.');

        return Command::SUCCESS;
    }

    /**
     * Clear cache for page and locale
     */
    protected function clearCache($pageId, $locale = null)
    {
        if (!config('content.cache.enabled', true)) {
            return;
        }

        if ($locale) {
            $cacheKey = config('content.cache.key_prefix', 'laravel_content_') . $pageId . '_' . $locale;
            Cache::forget($cacheKey);
        } else {
            // Clear for all locales
            $locales = array_keys(config('content.locale.available', ['en' => 'English']));
            foreach ($locales as $loc) {
                $cacheKey = config('content.cache.key_prefix', 'laravel_content_') . $pageId . '_' . $loc;
                Cache::forget($cacheKey);
            }
        }
    }

    /**
     * Truncate long values for display
     */
    protected function truncate($value, $length = 50)
    {
        return strlen($value) > $length
            ? substr($value, 0, $length) . '...'
            : $value;
    }
}
