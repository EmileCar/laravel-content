<?php

namespace Carone\Content\Console\Commands;

use Carone\Content\Models\PageContent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class CreateContentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'content:create
                            {--page= : The page identifier}
                            {--element= : The element identifier}
                            {--locale= : The locale (e.g., en, nl, fr)}
                            {--type= : The content type (text, image, file)}
                            {--value= : The content value}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new page content entry';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎨 Create New Page Content');
        $this->newLine();

        // Get page ID with validation
        $pageId = $this->option('page') ?? $this->ask('Page identifier (e.g., home, about, blog/post-1)');

        if (!$this->isValidPageId($pageId)) {
            $this->error("❌ Invalid page ID: '{$pageId}'");
            $this->warn('Page ID must:');
            $this->warn('  • Start and end with alphanumeric characters');
            $this->warn('  • Can contain: letters, numbers, hyphens, underscores, dots, forward slashes');
            $this->warn('  • Cannot be just "/" or contain "//"');
            $this->newLine();
            $suggestion = $this->suggestPageId($pageId);
            $this->info("💡 Try using: {$suggestion}");

            return Command::FAILURE;
        }

        // Get locale
        $availableLocales = array_keys(config('content.locale.available', ['en' => 'English']));
        $defaultLocale = PageContent::getDefaultLocale();

        $locale = $this->option('locale');
        if (!$locale) {
            if (count($availableLocales) > 1) {
                $localeChoices = array_map(function($loc) use ($defaultLocale) {
                    return $loc . ($loc === $defaultLocale ? ' (default)' : '');
                }, $availableLocales);
                $selectedIndex = array_search($defaultLocale, $availableLocales);
                $locale = $this->choice('Select locale', $localeChoices, $selectedIndex);
                $locale = explode(' ', $locale)[0]; // Remove "(default)" suffix
            } else {
                $locale = $defaultLocale;
                $this->info("Using default locale: {$locale}");
            }
        }

        // Get content type
        $contentTypes = config('content.content_types', ['text', 'image', 'file']);
        $type = $this->option('type') ?? $this->choice(
            'Content type',
            $contentTypes,
            0
        );

        // Get element ID
        $elementId = $this->option('element') ?? $this->ask('Element identifier (e.g., hero-title, about-text)');

        // Check if already exists
        if (PageContent::where('page_id', $pageId)
            ->where('element_id', $elementId)
            ->where('locale', $locale)
            ->exists()) {
            $this->error("❌ Content with element '{$elementId}' already exists on page '{$pageId}' for locale '{$locale}'!");

            if ($this->confirm('Do you want to update it instead?', false)) {
                return $this->updateExisting($pageId, $elementId, $locale, $type);
            }

            return Command::FAILURE;
        }

        // Get value with context-aware prompts
        $value = $this->option('value') ?? $this->askForValue($type);

        // Create the content
        $content = PageContent::create([
            'page_id' => $pageId,
            'element_id' => $elementId,
            'locale' => $locale,
            'type' => $type,
            'value' => $value,
        ]);

        // Clear cache
        $this->clearCache($pageId, $locale);

        $this->newLine();
        $this->info('✅ Content created successfully!');
        $this->newLine();

        $this->table(
            ['ID', 'Page', 'Element', 'Locale', 'Type', 'Value'],
            [[$content->id, $content->page_id, $content->element_id, $content->locale, $content->type, $this->truncate($content->value)]]
        );

        $this->newLine();
        $this->comment('💡 Add this component to your view:');
        if ($locale !== $defaultLocale) {
            $this->line("   <x-editable-{$type} element=\"{$elementId}\" locale=\"{$locale}\" />");
        } else {
            $this->line("   <x-editable-{$type} element=\"{$elementId}\" />");
        }
        $this->newLine();

        return Command::SUCCESS;
    }

    /**
     * Update existing content
     */
    protected function updateExisting($pageId, $elementId, $locale, $type)
    {
        $content = PageContent::where('page_id', $pageId)
            ->where('element_id', $elementId)
            ->where('locale', $locale)
            ->first();

        $this->info("Current value: {$this->truncate($content->value)}");
        $newValue = $this->ask('Enter new value');

        $content->update([
            'type' => $type,
            'value' => $newValue,
        ]);

        $this->clearCache($pageId, $locale);

        $this->info('✅ Content updated successfully!');

        return Command::SUCCESS;
    }

    /**
     * Ask for value based on content type
     */
    protected function askForValue($type)
    {
        $prompts = [
            'text' => 'Enter text content',
            'image' => 'Enter image URL or path (e.g., /images/hero.jpg)',
            'file' => 'Enter file URL or path (e.g., /documents/terms.pdf)',
        ];

        return $this->ask($prompts[$type] ?? 'Enter content value');
    }

    /**
     * Validate page ID
     */
    protected function isValidPageId($pageId)
    {
        if ($pageId === '/' || str_contains($pageId, '//')) {
            return false;
        }

        return (bool) preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-_.\/]*[a-zA-Z0-9]$/', $pageId);
    }

    /**
     * Suggest alternative page ID
     */
    protected function suggestPageId($pageId)
    {
        if ($pageId === '/') {
            return 'home';
        }

        if (str_contains($pageId, '//')) {
            return preg_replace('/\/+/', '/', $pageId);
        }

        return $pageId;
    }

    /**
     * Clear cache for page
     */
    protected function clearCache($pageId, $locale = null)
    {
        if (config('content.cache.enabled', true)) {
            $locale = $locale ?? PageContent::getDefaultLocale();
            $cacheKey = config('content.cache.key_prefix', 'laravel_content_') . $pageId . '_' . $locale;
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
