<?php

namespace Carone\Content\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class EditableFile extends Component
{
    public string $element;
    public $attributes;

    public function __construct(string $element, $attributes = [])
    {
        $this->element = $element;
        $this->attributes = $attributes;
    }

    public function render()
    {
        $elementId = $this->element;
        $contents = get_content();
        $authenticated = auth()->check();
        $default = config('content.defaults.file', 'files/placeholder.pdf');
        $value = $contents->get($elementId) ?? $default;

        return view('laravel-content::components.editable-file', compact('value', 'elementId', 'authenticated'));
    }
}
