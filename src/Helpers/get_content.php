<?php

use Carone\Content\Models\PageContent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;

if (!function_exists('get_content')) {
    /**
     * Get all content for the current page/route.
     *
     * @param string|null $locale The locale to fetch content for (defaults to app locale)
     * @param bool $resetCache Reset the static cache (useful for testing)
     * @return Collection
     */
    function get_content(?string $locale = null, bool $resetCache = false): Collection
    {
        // Get the locale to use
        $locale = $locale ?? PageContent::getDefaultLocale();
        $localeEnabled = config('content.locale.enabled', true);
        $fallbackEnabled = config('content.locale.fallback', true);

        // Static cache to avoid multiple DB/cache hits in the same request
        static $cachedContents = [];

        if ($resetCache) $cachedContents = [];

        $currentPage = Route::currentRouteName();
        $cacheIdentifier = $currentPage . '_' . $locale;

        if (isset($cachedContents[$cacheIdentifier])) {
            return $cachedContents[$cacheIdentifier];
        }

        // Secondary cache using Laravel's cache system if enabled
        if (config('content.cache.enabled', true)) {
            $cacheKey = config('content.cache.key_prefix', 'laravel_content_') . $cacheIdentifier;
            $cacheTtl = config('content.cache.ttl', 3600);

            $contents = Cache::remember($cacheKey, $cacheTtl, function () use ($currentPage, $locale, $localeEnabled, $fallbackEnabled) {
                return fetchContentForPage($currentPage, $locale, $localeEnabled, $fallbackEnabled);
            });
        } else {
            $contents = fetchContentForPage($currentPage, $locale, $localeEnabled, $fallbackEnabled);
        }

        $cachedContents[$cacheIdentifier] = $contents;

        return $contents;
    }
}

if (!function_exists('fetchContentForPage')) {
    /**
     * Fetch content for a specific page and locale
     *
     * @param string $pageId
     * @param string $locale
     * @param bool $localeEnabled
     * @param bool $fallbackEnabled
     * @return Collection
     */
    function fetchContentForPage(string $pageId, string $locale, bool $localeEnabled, bool $fallbackEnabled): Collection
    {
        if (!$localeEnabled) {
            // Locale support disabled, fetch content without locale filter
            return PageContent::where('page_id', $pageId)
                ->get(['element_id', 'value', 'type'])
                ->keyBy('element_id')
                ->map(fn($item) => $item->value);
        }

        // Fetch content for requested locale
        $contents = PageContent::where('page_id', $pageId)
            ->where('locale', $locale)
            ->get(['element_id', 'value', 'type'])
            ->keyBy('element_id')
            ->map(fn($item) => $item->value);

        // If fallback is enabled and we're not using the default locale, fetch missing content from default locale
        if ($fallbackEnabled && $locale !== PageContent::getDefaultLocale() && $contents->isEmpty()) {
            $defaultLocale = PageContent::getDefaultLocale();
            $defaultContents = PageContent::where('page_id', $pageId)
                ->where('locale', $defaultLocale)
                ->get(['element_id', 'value', 'type'])
                ->keyBy('element_id')
                ->map(fn($item) => $item->value);

            // Merge with priority to requested locale
            $contents = $defaultContents->merge($contents);
        }

        return $contents;
    }
}

if (!function_exists('clear_static_content_cache')) {
    /**
     * Clear the static content cache for the current request.
     *
     * @return void
     */
    function clear_static_content_cache(): void
    {
        get_content(resetCache: true);
    }
}
