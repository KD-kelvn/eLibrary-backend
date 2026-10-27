@php
    $branding = $branding ?? app(\App\Support\Mail\MailBranding::class)->resolve();
    $greeting = filled($greetingName ?? null) ? 'Hello ' . $greetingName . ',' : 'Hello,';
@endphp

@component('emails.layouts.branded', [
    'branding' => $branding,
    'title' => $title,
    'preheader' => $preheader ?? $title,
])
<h1 class="email-title" style="margin:0 0 16px 0;font-size:24px;line-height:32px;font-weight:700;color:#0A0A0A;">
    {{ $title }}
</h1>

<p style="margin:0 0 16px 0;font-size:16px;line-height:1.55;color:#3F3F46;">
    {{ $greeting }}
</p>

<p style="margin:0 0 24px 0;font-size:16px;line-height:1.55;color:#3F3F46;">
    {{ $body }}
</p>

@if (!empty($actionUrl))
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px 0;">
    <tr>
        <td align="center" bgcolor="{{ $branding['primary_color'] }}" style="border-radius:8px;">
            <a href="{{ $actionUrl }}"
             style="display:inline-block;padding:12px 22px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;">
                {{ $actionText ?? $branding['app_name'] }}
            </a>
        </td>
    </tr>
</table>
@endif
@endcomponent
