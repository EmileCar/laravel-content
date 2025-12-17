<?php

namespace Carone\Content\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class EditableP extends Component
{
    public string $element;
    public $attributes;

    public function __construct(string $element, $attributes = [])
    {
        $this->element = $element;
        $this->attributes = $attributes;
    }

    public function render(): View
    {
        $elementId = $this->element;
        $contents = get_content();
        $authenticated = auth()->check();
        $default = config('content.defaults.text', '-- No content available --');
        $value = $contents->get($elementId) ?? $default;

        return view('laravel-content::components.editable-p', compact('value', 'elementId', 'authenticated'));
    }
}
