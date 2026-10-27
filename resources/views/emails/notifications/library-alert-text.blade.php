{{ $title }}

{{ $body }}

@if (!empty($actionUrl))
{{ $actionText ?? 'Open' }}: {{ $actionUrl }}
@endif
