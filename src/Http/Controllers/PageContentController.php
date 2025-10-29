<?php

namespace Carone\Content\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Carone\Content\Models\PageContent;
use Carone\Content\Services\ContentService;
use Carone\Content\Services\JsonSchemaValidator;
use Carone\Content\Exceptions\ValidationException;

class PageContentController extends Controller
{
    public function __construct(
        private ContentService $contentService,
        private JsonSchemaValidator $validator
    ) {}

    /**
     * Show the content management dashboard
     */
    public function dashboard()
    {
        return view('laravel-content::index');
    }

    /**
     * Show the content editor for a specific page or new page
     */
    public function editor(string $identifier = null)
    {
        $page = null;
        
        if ($identifier) {
            $page = PageContent::findByNameOrId($identifier);
            if (!$page) {
                abort(404, 'Page not found');
            }
        }

        return view('laravel-content::editor', compact('page'));
    }

    /**
     * List pages with pagination and filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $pages = $this->contentService->getPages($request);
            return Response::json($pages);
        } catch (\Exception $e) {
            return Response::json(['error' => 'Failed to retrieve pages'], 500);
        }
    }

    /**
     * Create a new page
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:page_contents,name',
                'display_name' => 'required|string|max:255',
                'value' => 'nullable|array',
                'type' => 'string|max:255',
                'locale' => 'nullable|string|max:10|regex:/^[a-z]{2}(_[A-Z]{2})?$/',
            ]);

            if ($validator->fails()) {
                return Response::json(['error' => $validator->errors()], 422);
            }

            $data = $validator->validated();
            
            // Validate JSON structure if provided
            if (isset($data['value'])) {
                $this->validator->validatePage($data['value']);
            }

            $page = PageContent::create($data);
            
            // Clear related cache
            $this->contentService->clearPageCache($page->name, $page->locale);

            return Response::json($page, 201);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return Response::json(['error' => 'Failed to create page'], 500);
        }
    }

    /**
     * Show a specific page
     */
    public function show(string $identifier): JsonResponse
    {
        try {
            $page = PageContent::findByNameOrId($identifier);
            
            if (!$page) {
                return Response::json(['error' => 'Page not found'], 404);
            }

            return Response::json($page);
        } catch (\Exception $e) {
            return Response::json(['error' => 'Failed to retrieve page'], 500);
        }
    }

    /**
     * Update a page with optimistic concurrency control
     */
    public function update(Request $request, string $identifier): JsonResponse
    {
        try {
            $page = PageContent::findByNameOrId($identifier);
            
            if (!$page) {
                return Response::json(['error' => 'Page not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255|unique:page_contents,name,' . $page->id,
                'display_name' => 'sometimes|string|max:255',
                'value' => 'sometimes|nullable|array',
                'type' => 'sometimes|string|max:255',
                'locale' => 'sometimes|nullable|string|max:10|regex:/^[a-z]{2}(_[A-Z]{2})?$/',
                'version' => 'sometimes|integer|min:1',
            ]);

            if ($validator->fails()) {
                return Response::json(['error' => $validator->errors()], 422);
            }

            $data = $validator->validated();
            
            // Check version for optimistic concurrency control
            if (isset($data['version']) && !$page->hasCorrectVersion($data['version'])) {
                return Response::json([
                    'error' => 'Conflict: Page has been modified by another user',
                    'current_version' => $page->version
                ], 409);
            }

            // Validate JSON structure if provided
            if (isset($data['value'])) {
                $this->validator->validatePage($data['value']);
            }

            // Update the page
            $page->fill($data);
            $page->incrementVersion();
            $page->save();
            
            // Clear cache
            $this->contentService->clearPageCache($page->name, $page->locale);

            return Response::json($page);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return Response::json(['error' => 'Failed to update page'], 500);
        }
    }

    /**
     * Delete a page (soft delete)
     */
    public function destroy(string $identifier): JsonResponse
    {
        try {
            $page = PageContent::findByNameOrId($identifier);
            
            if (!$page) {
                return Response::json(['error' => 'Page not found'], 404);
            }

            $page->delete();
            
            // Clear cache
            $this->contentService->clearPageCache($page->name, $page->locale);

            return Response::json(['message' => 'Page deleted successfully']);
        } catch (\Exception $e) {
            return Response::json(['error' => 'Failed to delete page'], 500);
        }
    }

    /**
     * Export pages for backup
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $query = PageContent::query();
            
            // Apply filters if provided
            if ($request->has('type')) {
                $query->ofType($request->input('type'));
            }
            
            if ($request->has('locale')) {
                $query->ofLocale($request->input('locale'));
            }

            $pages = $query->get();
            
            $export = [
                'exported_at' => \Carbon\Carbon::now()->toISOString(),
                'version' => '1.0',
                'pages' => $pages->toArray()
            ];

            return Response::json($export);
        } catch (\Exception $e) {
            return Response::json(['error' => 'Failed to export pages'], 500);
        }
    }

    /**
     * Import pages from backup
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'pages' => 'required|array',
                'pages.*.name' => 'required|string',
                'pages.*.display_name' => 'required|string',
                'pages.*.type' => 'string',
                'pages.*.locale' => 'nullable|string',
                'pages.*.value' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return Response::json(['error' => $validator->errors()], 422);
            }

            $imported = 0;
            $errors = [];

            foreach ($request->input('pages') as $pageData) {
                try {
                    // Validate JSON structure if provided
                    if (isset($pageData['value'])) {
                        $this->validator->validatePage($pageData['value']);
                    }

                    // Check if page already exists
                    $existing = PageContent::where('name', $pageData['name'])->first();
                    
                    if ($existing) {
                        $existing->update($pageData);
                        $existing->incrementVersion();
                        $existing->save();
                    } else {
                        PageContent::create($pageData);
                    }
                    
                    $imported++;
                    
                    // Clear cache for this page
                    $this->contentService->clearPageCache(
                        $pageData['name'], 
                        $pageData['locale'] ?? null
                    );
                } catch (\Exception $e) {
                    $errors[] = "Failed to import page '{$pageData['name']}': " . $e->getMessage();
                }
            }

            return Response::json([
                'message' => "Import completed. {$imported} pages imported.",
                'imported' => $imported,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            return Response::json(['error' => 'Failed to import pages'], 500);
        }
    }
}