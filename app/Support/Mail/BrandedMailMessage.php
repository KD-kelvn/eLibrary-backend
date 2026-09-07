<?php

namespace App\Support\Mail;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Factory for project-wide branded transactional emails.
 *
 * Prefer html()/text() views under resources/views/emails for reliable
 * rendering across clients. Markdown templates escape nested HTML in
 * Laravel 12, so avoid them for layout-heavy messages.
 */
class BrandedMailMessage extends MailMessage
{
    public static function make(?string $subject = null): self
    {
        $branding = app(MailBranding::class)->resolve();

        $message = (new self)
            ->from(
                (string) config('mail.from.address'),
                $branding['app_name'],
            );

        if ($subject !== null && $subject !== '') {
            $message->subject($subject);
        }

        return $message;
    }

    /**
     * Attach a branded HTML view (and optional plain-text twin).
     *
     * @param  array<string, mixed>  $data
     */
    public function brandedView(string $htmlView, array $data = [], ?string $textView = null): self
    {
        $branding = app(MailBranding::class)->resolve();
        $payload = array_merge(['branding' => $branding], $data);

        if ($textView) {
            return $this->view([
                'html' => $htmlView,
                'text' => $textView,
            ], $payload);
        }

        return $this->view($htmlView, $payload);
    }
}
