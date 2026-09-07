{{ $title }}

Hello{{ filled($greetingName ?? null) ? ' ' . $greetingName : '' }},

{{ $intro }}

Your one-time code: {{ $code }}

This code expires in {{ $expiryMinutes }} minutes. For your security, do not share it with anyone.

@isset($actionUrl)
      {{ $actionText }}: {{ $actionUrl }}
@endisset

{{ $outro }}

Thanks,
{{ $appName }} Team

© {{ date('Y') }} {{ $appName }}. All rights reserved.
{{ $branding['frontend_url'] ?? '' }}