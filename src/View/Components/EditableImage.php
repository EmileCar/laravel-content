<?php

namespace Carone\Content\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class EditableImage extends Component
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
        $default = config('content.defaults.image', 'images/placeholder.png');
        $value = $contents->get($elementId) ?? $default;

        return view('laravel-content::components.editable-image', compact('value', 'elementId', 'authenticated'));
    }
}
