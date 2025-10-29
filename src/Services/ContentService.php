<?php

namespace Carone\Content\Services;

use Carone\Content\Models\PageContent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;

class ContentService
{
    private array $requestCache = [];
    private bool $cacheEnabled;
    private int $cacheTtl;
    private string $cacheKeyPrefix;

    public function __construct()
    {
        $this->cacheEnabled = Config::get('content.cache.enabled', true);
        $this->cacheTtl = Config::get('content.cache.ttl', 3600);
        $this->cacheKeyPrefix = Config::get('content.cache.key_prefix', 'laravel_content_');
    }

    /**
     * Get page content by name with caching
     */
    public function getPageByName(string $name, ?string $locale = null): ?PageContent
    {
        // Check request-level cache first
        $cacheKey = $this->getRequestCacheKey($name, $locale);
        
        if (isset($this->requestCache[$cacheKey])) {
            return $this->requestCache[$cacheKey];
        }

        // Check Laravel cache if enabled
        if ($this->cacheEnabled) {
            $systemCacheKey = $this->getSystemCacheKey($name, $locale);
            $page = Cache::remember($systemCacheKey, $this->cacheTtl, function () use ($name, $locale) {
                return $this->fetchPageFromDatabase($name, $locale);
            });
        } else {
            $page = $this->fetchPageFromDatabase($name, $locale);
        }

        // Store in request cache
        $this->requestCache[$cacheKey] = $page;

        return $page;
    }

    /**
     * Get block value from a page with caching
     */
    public function getBlockValue(string $pageName, string $blockId, ?string $key = null, ?string $locale = null): mixed
    {
        $page = $this->getPageByName($pageName, $locale);
        
        if (!$page) {
            return null;
        }

        return $page->getBlockValue($blockId, $key);
    }

    /**
     * Clear cache for a specific page
     */
    public function clearPageCache(string $name, ?string $locale = null): void
    {
        $cacheKey = $this->getSystemCacheKey($name, $locale);
        Cache::forget($cacheKey);
        
        // Also clear from request cache
        $requestCacheKey = $this->getRequestCacheKey($name, $locale);
        unset($this->requestCache[$requestCacheKey]);
    }

    /**
     * Clear all content cache
     */
    public function clearAllCache(): void
    {
        Cache::flush(); // This will clear all cache, you might want to be more specific
        $this->requestCache = [];
    }

    /**
     * Get pages with filtering and pagination
     */
    public function getPages(Request $request)
    {
        $query = PageContent::query();

        // Apply filters
        if ($request->has('type')) {
            $query->ofType($request->input('type'));
        }

        if ($request->has('locale')) {
            $query->ofLocale($request->input('locale'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('display_name', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginate
        $perPage = min(
            $request->input('per_page', Config::get('content.pagination.per_page', 15)),
            Config::get('content.pagination.max_per_page', 100)
        );

        return $query->paginate($perPage);
    }

    /**
     * Fetch page from database
     */
    private function fetchPageFromDatabase(string $name, ?string $locale = null): ?PageContent
    {
        $query = PageContent::where('name', $name);
        
        if ($locale) {
            $query->where('locale', $locale);
        }

        return $query->first();
    }

    /**
     * Generate request-level cache key
     */
    private function getRequestCacheKey(string $name, ?string $locale = null): string
    {
        return $name . '|' . ($locale ?? 'null');
    }

    /**
     * Generate system cache key
     */
    private function getSystemCacheKey(string $name, ?string $locale = null): string
    {
        return $this->cacheKeyPrefix . $name . '|' . ($locale ?? 'null');
    }
}