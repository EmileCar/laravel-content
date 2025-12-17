<p {{ $attributes->merge([
    'class' => $authenticated
                ? trim(($attributes['class'] ?? '') . ' editable-p')
                : ($attributes['class'] ?? ''),
    'data-content-id' => $authenticated ? $elementId : null,
    'style' => 'white-space: pre-line;'
]) }}>{{ $value }}</p>
