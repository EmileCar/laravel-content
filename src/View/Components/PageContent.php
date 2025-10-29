<?php

namespace Carone\Content\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use Illuminate\Support\Facades\App;
use Carone\Content\Services\ContentService;
use Illuminate\Http\Request;

class PageContent extends Component
{
    public string $pageName;
    public string $block;
    public ?string $key;
    public ?string $locale;
    public mixed $default;

    public function __construct(
        private ContentService $contentService,
        string $page = '',
        string $block = '',
        ?string $key = null,
        ?string $locale = null,
        mixed $default = null
    ) {
        $this->pageName = $page ?: $this->getCurrentPageName();
        $this->block = $block;
        $this->key = $key;
        $this->locale = $locale ?: App::getLocale();
        $this->default = $default;
    }

    public function render(): View|string
    {
        $value = $this->contentService->getBlockValue(
            $this->pageName,
            $this->block,
            $this->key,
            $this->locale
        );

        // Return the value directly if it exists, otherwise return default
        if ($value !== null) {
            return (string) $value;
        }

        if ($this->default !== null) {
            return (string) $this->default;
        }

        // Return empty string if no value and no default
        return '';
    }

    /**
     * Get the current page name from the route or request
     */
    private function getCurrentPageName(): string
    {
        $request = App::make(Request::class);
        
        // Try to get page name from route parameter
        if ($request->route() && $request->route()->hasParameter('page')) {
            return $request->route()->parameter('page');
        }

        // Try to get from route name
        if ($request->route() && $request->route()->getName()) {
            return $request->route()->getName();
        }

        // Fall back to path
        return trim($request->path(), '/') ?: 'home';
    }
}