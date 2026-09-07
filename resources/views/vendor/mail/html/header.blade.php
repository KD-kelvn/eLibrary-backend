@props(['url'])
@php($branding = app(\App\Support\Mail\MailBranding::class)->resolve())
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;" target="_blank" rel="noopener">
@if (! empty($branding['logo_url']))
<img src="{{ $branding['logo_url'] }}" class="logo" alt="{{ $branding['app_name'] }}">
@elseif (trim($slot) !== '')
<span class="brand-mark">{!! $slot !!}</span>
@else
<span class="brand-mark">{{ $branding['app_name'] }}</span>
@endif
</a>
</td>
</tr>
