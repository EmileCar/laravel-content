<?php

namespace Carone\Content\Console\Commands;

use Carone\Content\Models\PageContent;
use Illuminate\Console\Command;

class ListContentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'content:list
                            {--page= : Show content for a specific page only}
                            {--type= : Filter by content type (text, image, file)}
                            {--full : Show full values without truncation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display page content in a table format';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = PageContent::query();

        // Apply filters
        if ($page = $this->option('page')) {
            $query->where('page_id', $page);
        }

        if ($type = $this->option('type')) {
            $query->where('type', $type);
        }

        $contents = $query->orderBy('page_id')->orderBy('element_id')->get();

        if ($contents->isEmpty()) {
            $this->warn('No content found.');
            return Command::SUCCESS;
        }

        $this->info('📋 Page Content');
        $this->newLine();

        // Group by page if showing all pages
        if (!$this->option('page')) {
            $this->displayGroupedByPage($contents);
        } else {
            $this->displayTable($contents);
        }

        $this->newLine();
        $this->info("Total: {$contents->count()} content entries");

        return Command::SUCCESS;
    }

    /**
     * Display content grouped by page
     */
    protected function displayGroupedByPage($contents)
    {
        $pages = $contents->groupBy('page_id');

        foreach ($pages as $pageId => $pageContents) {
            $this->comment("Page: {$pageId} ({$pageContents->count()} items)");
            $this->line(str_repeat('─', 80));
            
            $this->table(
                ['ID', 'Element ID', 'Type', 'Value'],
                $pageContents->map(fn($c) => [
                    $c->id,
                    $c->element_id,
                    $this->coloredType($c->type),
                    $this->formatValue($c->value)
                ])
            );
            
            $this->newLine();
        }
    }

    /**
     * Display content in a single table
     */
    protected function displayTable($contents)
    {
        $this->table(
            ['ID', 'Page ID', 'Element ID', 'Type', 'Value'],
            $contents->map(fn($c) => [
                $c->id,
                $c->page_id,
                $c->element_id,
                $this->coloredType($c->type),
                $this->formatValue($c->value)
            ])
        );
    }

    /**
     * Format value for display
     */
    protected function formatValue($value)
    {
        if ($this->option('full')) {
            return $value;
        }

        $maxLength = 60;
        
        if (strlen($value) > $maxLength) {
            return substr($value, 0, $maxLength) . '...';
        }

        return $value;
    }

    /**
     * Add color to content type
     */
    protected function coloredType($type)
    {
        $colors = [
            'text' => 'white',
            'image' => 'comment',
            'file' => 'question',
        ];

        $color = $colors[$type] ?? 'info';
        
        return "<fg={$color}>{$type}</>";
    }
}
