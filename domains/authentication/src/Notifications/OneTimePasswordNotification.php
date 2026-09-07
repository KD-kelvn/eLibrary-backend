<?php

namespace Modules\Authentication\Notifications;

use App\Support\Mail\BrandedMailMessage;
use App\Support\Mail\MailBranding;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Authentication\Enums\OneTimeTokenPurpose;
use Modules\Authentication\Enums\OneTimeTokenVia;

class OneTimePasswordNotification extends Notification
{
    public function __construct(
        private readonly string $code,
        private readonly OneTimeTokenVia $via,
        private readonly int $expiryMinutes,
        private readonly OneTimeTokenPurpose $purpose = OneTimeTokenPurpose::Login,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return match ($this->via) {
            OneTimeTokenVia::Email => ['mail'],
            OneTimeTokenVia::Phone => ['log_sms'],
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $branding = app(MailBranding::class)->resolve();
        $isReset = $this->purpose === OneTimeTokenPurpose::PasswordReset;

        if (method_exists($notifiable, 'loadMissing')) {
            $notifiable->loadMissing('profile');
        }

        $greetingName = $notifiable->profile?->fullname
            ?? $notifiable->username
            ?? null;

        $title = $isReset ? 'Reset your password' : 'Your sign-in code';
        $intro = $isReset
            ? 'We received a request to reset the password for your '.$branding['app_name'].' account. Use the one-time code below to continue.'
            : 'Use the one-time code below to finish signing in to your '.$branding['app_name'].' account.';
        $outro = $isReset
            ? 'If you did not request a password reset, you can safely ignore this email. Your password will stay the same.'
            : 'If you did not try to sign in, you can safely ignore this email.';
        $actionUrl = $isReset
            ? $branding['frontend_url'].'/authentication/forgot-password'
            : $branding['frontend_url'].'/authentication/login';
        $actionText = $isReset ? 'Continue password reset' : 'Open '.$branding['app_name'];
        $subject = $isReset
            ? $branding['app_name'].' password reset code'
            : $branding['app_name'].' sign-in code';

        return BrandedMailMessage::make($subject)
            ->brandedView(
                'emails.auth.one-time-password',
                [
                    'title' => $title,
                    'greetingName' => $greetingName,
                    'intro' => $intro,
                    'code' => $this->code,
                    'expiryMinutes' => $this->expiryMinutes,
                    'actionUrl' => $actionUrl,
                    'actionText' => $actionText,
                    'outro' => $outro,
                    'appName' => $branding['app_name'],
                ],
                'emails.auth.one-time-password-text',
            );
    }

    public function toLogSms(object $notifiable): string
    {
        $isReset = $this->purpose === OneTimeTokenPurpose::PasswordReset;
        $label = $isReset ? 'password reset code' : 'login code';

        return "Your {$label} is {$this->code}. It expires in {$this->expiryMinutes} minutes.";
    }
}
