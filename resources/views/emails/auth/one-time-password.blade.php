@php
    $branding = $branding ?? app(\App\Support\Mail\MailBranding::class)->resolve();
    $primary = $branding['primary_color'];
    $greeting = filled($greetingName ?? null) ? 'Hello ' . $greetingName . ',' : 'Hello,';
@endphp

@component('emails.layouts.branded', [
    'branding' => $branding,
    'title' => $title,
    'preheader' => 'Your verification code is ' . $code . '. It expires in ' . $expiryMinutes . ' minutes.',
])
<h1 class="email-title" style="margin:0 0 16px 0;font-size:24px;line-height:32px;font-weight:700;color:#0A0A0A;">
    {{ $title }}
</h1>

<p style="margin:0 0 16px 0;font-size:16px;line-height:1.55;color:#3F3F46;">
    {{ $greeting }}
</p>

<p style="margin:0 0 24px 0;font-size:16px;line-height:1.55;color:#3F3F46;">
    {{ $intro }}
</p>

{{-- OTP panel --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
 style="width:100%;margin:0 0 24px 0;background-color:#F4F4F5;border-left:4px solid {{ $primary }};border-radius:8px;">
    <tr>
        <td align="center" style="padding:22px 16px;">
            <p
             style="margin:0 0 8px 0;font-size:12px;line-height:1.4;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#71717A;">
                One-time code
            </p>
            <p class="otp-code"
             style="margin:0;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,'Liberation Mono','Courier New',monospace;font-size:32px;line-height:1.2;font-weight:700;letter-spacing:0.35em;color:#0A0A0A;">
                {{ $code }}
            </p>
        </td>
    </tr>
</table>

<p style="margin:0 0 24px 0;font-size:16px;line-height:1.55;color:#3F3F46;">
    This code expires in <strong style="color:#0A0A0A;">{{ $expiryMinutes }} minutes</strong>.
    For your security, do not share it with anyone.
</p>

@isset($actionUrl)
    {{-- Bulletproof button --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
     style="width:100%;margin:0 0 28px 0;">
        <tr>
            <td align="center">
                <table role="presentation" class="btn-primary" cellpadding="0" cellspacing="0" border="0"
                 style="margin:0 auto;">
                    <tr>
                        <td align="center" bgcolor="{{ $primary }}"
                         style="border-radius:8px;background-color:{{ $primary }};">
                            <a href="{{ $actionUrl }}" target="_blank" rel="noopener"
                             style="display:inline-block;padding:14px 28px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:16px;font-weight:700;line-height:1.25;color:#ffffff;text-decoration:none;border-radius:8px;">
                                {{ $actionText }}
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endisset

<p style="margin:0 0 24px 0;font-size:15px;line-height:1.55;color:#71717A;">
    {{ $outro }}
</p>

<p style="margin:0;font-size:16px;line-height:1.55;color:#3F3F46;">
    Thanks,<br>
    <strong style="color:#0A0A0A;">{{ $appName }} Team</strong>
</p>
@endcomponent