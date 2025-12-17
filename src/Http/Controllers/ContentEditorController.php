<?php

namespace Carone\Content\Http\Controllers;

use Carone\Content\Models\PageContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ContentEditorController
{
    /**
     * Display the content editor dashboard
     */
    public function index()
    {
        $pages = PageContent::select('page_id')
            ->groupBy('page_id')
            ->get()
            ->pluck('page_id');

        return view('laravel-content::editor.index', compact('pages'));
    }

    /**
     * Get content for a specific page
     */
    public function getPageContent($pageId)
    {
        $contents = PageContent::where('page_id', $pageId)
            ->orderBy('element_id')
            ->get();

        return response()->json([
            'page_id' => $pageId,
            'contents' => $contents
        ]);
    }

    /**
     * Update or create content
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page_id' => 'required|string|max:255',
            'element_id' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', config('content.content_types', ['text', 'image', 'file'])),
            'value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $content = PageContent::updateOrCreate(
            [
                'page_id' => $request->page_id,
                'element_id' => $request->element_id,
            ],
            [
                'type' => $request->type,
                'value' => $request->value,
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
     * Clear cache for a specific page
     */
    protected function clearPageCache($pageId)
    {
        if (config('content.cache.enabled', true)) {
            $cacheKey = config('content.cache.key_prefix', 'laravel_content_') . $pageId;
            Cache::forget($cacheKey);
        }
    }
}
