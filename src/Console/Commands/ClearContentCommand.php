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

        if ($pageId) {
            return $this->clearPageContent($pageId);
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

        // Get all unique page IDs before deleting
        $pageIds = PageContent::select('page_id')->distinct()->pluck('page_id');

        // Delete all content
        PageContent::truncate();

        // Clear all caches
        foreach ($pageIds as $pageId) {
            $this->clearCache($pageId);
        }

        $this->newLine();
        $this->info("✅ Successfully deleted {$count} content entries!");
        $this->info('✅ Cache cleared for all pages.');

        return Command::SUCCESS;
    }

    /**
     * Clear content for a specific page
     */
    protected function clearPageContent($pageId)
    {
        $count = PageContent::where('page_id', $pageId)->count();

        if ($count === 0) {
            $this->info("No content found for page '{$pageId}'.");
            return Command::SUCCESS;
        }

        $this->warn("⚠️  This will delete {$count} content entries for page '{$pageId}'!");
        $this->newLine();

        if (!$this->option('force') && !$this->confirm('Are you sure you want to continue?', false)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        // Show what will be deleted
        $contents = PageContent::where('page_id', $pageId)->get();
        
        $this->table(
            ['Element ID', 'Type', 'Value'],
            $contents->map(fn($c) => [
                $c->element_id,
                $c->type,
                $this->truncate($c->value)
            ])
        );

        // Delete content
        PageContent::where('page_id', $pageId)->delete();

        // Clear cache
        $this->clearCache($pageId);

        $this->newLine();
        $this->info("✅ Successfully deleted {$count} content entries for page '{$pageId}'!");
        $this->info('✅ Cache cleared.');

        return Command::SUCCESS;
    }

    /**
     * Clear cache for page
     */
    protected function clearCache($pageId)
    {
        if (config('content.cache.enabled', true)) {
            $cacheKey = config('content.cache.key_prefix', 'laravel_content_') . $pageId;
            Cache::forget($cacheKey);
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
