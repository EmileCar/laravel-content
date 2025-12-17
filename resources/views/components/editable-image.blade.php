<img src="{{ asset($value) }}"
     {{ $attributes->merge([
         'class' => $authenticated
                     ? trim(($attributes['class'] ?? '') . ' editable-image')
                     : ($attributes['class'] ?? ''),
         'data-content-id' => $authenticated ? $elementId : null
     ]) }}
    @if($authenticated)
        style="cursor: pointer;"
    @endif
>

