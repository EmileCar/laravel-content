<?php

use Carone\Content\Models\PageContent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;

if (!function_exists('get_content')) {
    /**
     * Get all content for the current page/route.
     *
     * @param bool $resetCache Reset the static cache (useful for testing)
     * @return Collection
     */
    function get_content(bool $resetCache = false): Collection
    {

        // Static cache to avoid multiple DB/cache hits in the same request
        static $cachedContents = [];

        if ($resetCache) $cachedContents = [];

        $currentPage = Route::currentRouteName();

        if (isset($cachedContents[$currentPage])) {
            return $cachedContents[$currentPage];
        }

        // Secondary cache using Laravel's cache system if enabled
        if (config('content.cache.enabled', true)) {
            $cacheKey = config('content.cache.key_prefix', 'laravel_content_') . $currentPage;
            $cacheTtl = config('content.cache.ttl', 3600);

            $contents = Cache::remember($cacheKey, $cacheTtl, function () use ($currentPage) {
                return PageContent::where('page_id', $currentPage)
                    ->get(['element_id', 'value', 'type'])
                    ->keyBy('element_id')
                    ->map(fn($item) => $item->value);
            });
        } else {
            $contents = PageContent::where('page_id', $currentPage)
                ->get(['element_id', 'value', 'type'])
                ->keyBy('element_id')
                ->map(fn($item) => $item->value);
        }

        $cachedContents[$currentPage] = $contents;

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