<a href="{{ asset($value) }}"
   {{ $attributes->merge([
       'class' => $authenticated
                   ? trim(($attributes['class'] ?? '') . ' editable-file')
                   : ($attributes['class'] ?? ''),
       'data-content-id' => $authenticated ? $elementId : null,
       'download' => true
   ]) }}
   @if($authenticated)
       style="cursor: pointer;"
   @endif
>{{ $attributes->get('text') ?? basename($value) }}</a>
