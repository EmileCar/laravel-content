<?php

namespace Carone\Content\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    public function rules(): array
    {
        $pageId = $this->route('page') ? (is_numeric($this->route('page')) ? $this->route('page') : null) : null;
        $pageName = !is_numeric($this->route('page')) ? $this->route('page') : null;

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('page_contents', 'name')->ignore($pageId)->where(function ($query) use ($pageName) {
                    if ($pageName) {
                        $query->where('name', '!=', $pageName);
                    }
                })
            ],
            'display_name' => 'sometimes|string|max:255',
            'value' => 'sometimes|nullable|array',
            'type' => 'sometimes|string|max:255',
            'locale' => 'sometimes|nullable|string|max:10|regex:/^[a-z]{2}(_[A-Z]{2})?$/',
            'version' => 'sometimes|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A page with this name already exists.',
            'locale.regex' => 'Locale must be in format like "en" or "en_US".',
        ];
    }
}