<?php

namespace Carone\Content\Http\Controllers;

use Carone\Content\Models\PageContent;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

class ContentEditorController
{
    /**
     * Display the content editor dashboard
     */
    public function index()
    {
        $driver = DB::connection()->getDriverName();

        $distinctExpression = match ($driver) {
            'sqlite' => 'element_id || "-" || locale',
            default  => 'CONCAT(element_id, "-", locale)',
        };

        $pages = PageContent::select('page_id')
            ->selectRaw("COUNT(DISTINCT {$distinctExpression}) as content_count")
            ->groupBy('page_id')
            ->get()
            ->map(function ($page) {
                return [
                    'page_id' => $page->page_id,
                    'count' => $page->content_count,
                ];
            });

        $locales = config('content.locale.enabled', true)
            ? config('content.locale.available', ['en' => 'English'])
            : ['en' => 'English'];
        $defaultLocale = config('content.locale.default', 'en');

        return view('laravel-content::editor.index', compact('pages', 'locales', 'defaultLocale'));
    }

    /**
     * Get content for a specific page and locale
     */
    public function getPageContent($pageId, Request $request)
    {
        $locale = $request->input('locale', config('content.locale.default', 'en'));

        $contents = PageContent::where('page_id', $pageId)
            ->where('locale', $locale)
            ->orderBy('element_id')
            ->get();

        return response()->json([
            'page_id' => $pageId,
            'locale' => $locale,
            'contents' => $contents
        ]);
    }

    /**
     * Update or create content
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page_id' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9][a-zA-Z0-9\-_.\/]*[a-zA-Z0-9]$/',
                function ($attribute, $value, $fail) {
                    // Disallow single slash or paths that would create double slashes
                    if ($value === '/' || str_contains($value, '//')) {
                        $fail('The page ID cannot be "/" or contain "//". Use a valid identifier like "home" instead.');
                    }
                },
            ],
            'element_id' => 'required|string|max:255',
            'locale' => 'required|string|max:10',
            'type' => 'required|in:' . implode(',', config('content.content_types', ['text', 'image', 'file'])),
            'value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $content = PageContent::updateOrCreate(
            [
                'page_id' => $request->input('page_id'),
                'element_id' => $request->input('element_id'),
                'locale' => $request->input('locale'),
            ],
            [
                'type' => $request->input('type'),
                'value' => $request->input('value'),
            ]
        );

        // Clear cache for this page
        $this->clearPageCache($request->page_id);

        return response()->json([
            'success' => true,
            'content' => $content,
            'message' => 'Content saved successfully'
        ]);
    }

    /**
     * Delete content
     */
    public function destroy($id)
    {
        $content = PageContent::findOrFail($id);
        $pageId = $content->page_id;

        $content->delete();

        // Clear cache for this page
        $this->clearPageCache($pageId);

        return response()->json([
            'success' => true,
            'message' => 'Content deleted successfully'
        ]);
    }

    /**
     * Delete an entire page with all its content
     */
    public function destroyPage($pageId)
    {
        $count = PageContent::where('page_id', $pageId)->count();

        if ($count === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found'
            ], 404);
        }

        PageContent::where('page_id', $pageId)->delete();

        // Clear cache for this page
        $this->clearPageCache($pageId);

        return response()->json([
            'success' => true,
            'message' => "Page deleted successfully ({$count} content items removed)"
        ]);
    }

    /**
     * Clear cache for a specific page
     */
    protected function clearPageCache($pageId)
    {
        if (config('content.cache.enabled', true)) {
            $cacheKey = config('content.cache.key_prefix', 'laravel_content_') . $pageId;
            Cache::forget($cacheKey);
        }
    }

    /**
     * Get all web routes from the application (excluding dev tools and editor routes)
     */
    public function getWebRoutes()
    {
        $routes = collect(Route::getRoutes())->filter(function ($route) {
            $uri = $route->uri();
            $name = $route->getName();
            $methods = $route->methods();

            // Only include GET routes
            if (!in_array('GET', $methods)) {
                return false;
            }

            // Exclude API routes
            if (str_starts_with($uri, 'api/')) {
                return false;
            }

            // Exclude routes with parameters (e.g., {id}, {slug})
            if (preg_match('/\{.*\}/', $uri)) {
                return false;
            }

            // Exclude development/debugging routes
            $excludePatterns = [
                '_debugbar', 'debugbar', 'telescope', 'horizon',
                'ignition', 'livewire', 'nova', 'pulse',
                '_ignition', 'sanctum', 'broadcasting'
            ];

            foreach ($excludePatterns as $pattern) {
                if (stripos($uri, $pattern) !== false || ($name && stripos($name, $pattern) !== false)) {
                    return false;
                }
            }

            // Exclude the editor routes
            $editorPrefix = config('content.route_prefix', 'admin/content');
            if (str_starts_with($uri, $editorPrefix)) {
                return false;
            }

            return true;
        })->map(function ($route) {
            return [
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'display' => $route->getName() ?: $route->uri(),
            ];
        })->unique('uri')->values();

        return response()->json([
            'routes' => $routes
        ]);
    }
}

