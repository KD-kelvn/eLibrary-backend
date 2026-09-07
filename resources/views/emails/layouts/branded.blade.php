{{--
  Shared branded transactional email shell.
  Table-based + inline styles for client support; media queries for fluid width.
--}}
@php
  $branding = $branding ?? app(\App\Support\Mail\MailBranding::class)->resolve();
  $appName = $branding['app_name'];
  $primary = $branding['primary_color'];
  $frontendUrl = $branding['frontend_url'];
  $logoUrl = $branding['logo_url'] ?? null;
  $preheader = $preheader ?? '';
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml"
 xmlns:o="urn:schemas-microsoft-com:office:office">

  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $title ?? $appName }}</title>
    <!--[if mso]>
<noscript>
<xml>
<o:OfficeDocumentSettings>
<o:PixelsPerInch>96</o:PixelsPerInch>
</o:OfficeDocumentSettings>
</xml>
</noscript>
<![endif]-->
    <style>
      html,
      body {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        height: 100% !important;
      }

      * {
        -ms-text-size-adjust: 100%;
        -webkit-text-size-adjust: 100%;
      }

      table,
      td {
        mso-table-lspace: 0pt !important;
        mso-table-rspace: 0pt !important;
        border-collapse: collapse;
      }

      img {
        -ms-interpolation-mode: bicubic;
        border: 0;
        outline: none;
        text-decoration: none;
        display: block;
      }

      a {
        text-decoration: none;
      }

      .email-wrapper {
        width: 100% !important;
        background-color: #F4F4F5;
      }

      .email-body {
        width: 100%;
        max-width: 600px;
      }

      .email-card {
        width: 100%;
        max-width: 600px;
        background-color: #ffffff;
        border: 1px solid #E4E4E7;
        border-radius: 12px;
      }

      .stack-column {
        display: inline-block;
        width: 100%;
        vertical-align: top;
      }

      @media only screen and (max-width: 620px) {
        .email-padding {
          padding-left: 16px !important;
          padding-right: 16px !important;
        }

        .email-card-pad {
          padding: 24px 20px !important;
        }

        .email-title {
          font-size: 22px !important;
          line-height: 28px !important;
        }

        .otp-code {
          font-size: 28px !important;
          letter-spacing: 0.28em !important;
        }

        .btn-primary {
          display: block !important;
          width: 100% !important;
        }

        .btn-primary a {
          display: block !important;
          width: 100% !important;
          box-sizing: border-box !important;
        }
      }
    </style>
  </head>

  <body style="margin:0;padding:0;width:100%;background-color:#F4F4F5;color:#3F3F46;">
    @if ($preheader !== '')
      <div
       style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
        {{ $preheader }}
        &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
      </div>
    @endif

    <table role="presentation" class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" border="0"
     style="width:100%;background-color:#F4F4F5;">
      <tr>
        <td align="center" class="email-padding" style="padding:32px 24px;">

          {{-- Header / brand --}}
          <table role="presentation" class="email-body" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="width:100%;max-width:600px;margin:0 auto;">
            <tr>
              <td align="center" style="padding:0 0 24px 0;">
                <a href="{{ $frontendUrl }}" target="_blank" rel="noopener" style="text-decoration:none;">
                  @if ($logoUrl)
                    <img src="{{ $logoUrl }}" width="160" alt="{{ $appName }}"
                     style="height:auto;max-width:180px;width:160px;margin:0 auto;">
                  @else
                    <span
                     style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:28px;font-weight:700;line-height:1.2;letter-spacing:-0.02em;color:{{ $primary }};">
                      {{ $appName }}
                    </span>
                  @endif
                </a>
              </td>
            </tr>
          </table>

          {{-- Card --}}
          <table role="presentation" class="email-card" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="width:100%;max-width:600px;margin:0 auto;background-color:#ffffff;border:1px solid #E4E4E7;border-radius:12px;">
            <tr>
              <td class="email-card-pad"
               style="padding:36px 40px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:16px;line-height:1.55;color:#3F3F46;">
                {!! $slot !!}
              </td>
            </tr>
          </table>

          {{-- Footer --}}
          <table role="presentation" class="email-body" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="width:100%;max-width:600px;margin:0 auto;">
            <tr>
              <td align="center"
               style="padding:28px 12px 8px 12px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;line-height:1.5;color:#A1A1AA;">
                © {{ date('Y') }} {{ $appName }}. All rights reserved.<br>
                <a href="{{ $frontendUrl }}" target="_blank" rel="noopener"
                 style="color:{{ $primary }};text-decoration:underline;">Visit {{ $appName }}</a>
              </td>
            </tr>
          </table>

        </td>
      </tr>
    </table>
  </body>

</html>