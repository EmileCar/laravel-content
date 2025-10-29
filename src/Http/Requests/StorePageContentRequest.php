<?php

namespace Carone\Content\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePageContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:page_contents,name',
            'display_name' => 'required|string|max:255',
            'value' => 'nullable|array',
            'type' => 'string|max:255',
            'locale' => 'nullable|string|max:10|regex:/^[a-z]{2}(_[A-Z]{2})?$/',
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