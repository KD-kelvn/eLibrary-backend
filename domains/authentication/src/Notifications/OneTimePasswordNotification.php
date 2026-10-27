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
            // Active SmsProvider (log | beem | institutional). Quiet hours do not apply to OTP.
            OneTimeTokenVia::Phone => ['sms'],
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $this->localeFor($notifiable);
        $branding = app(MailBranding::class)->resolve();
        $isReset = $this->purpose === OneTimeTokenPurpose::PasswordReset;

        if (method_exists($notifiable, 'loadMissing')) {
            $notifiable->loadMissing('profile');
        }

        $greetingName = $notifiable->profile?->fullname
            ?? $notifiable->username
            ?? null;

        $app = $branding['app_name'];
        $title = $isReset
            ? __('notifications.otp.reset_title', [], $locale)
            : __('notifications.otp.login_title', [], $locale);
        $intro = $isReset
            ? __('notifications.otp.reset_intro', ['app' => $app], $locale)
            : __('notifications.otp.login_intro', ['app' => $app], $locale);
        $outro = $isReset
            ? __('notifications.otp.reset_outro', [], $locale)
            : __('notifications.otp.login_outro', [], $locale);
        $actionUrl = $isReset
            ? $branding['frontend_url'].'/authentication/forgot-password'
            : $branding['frontend_url'].'/authentication/login';
        $actionText = $isReset
            ? __('notifications.otp.reset_action', [], $locale)
            : __('notifications.otp.login_action', ['app' => $app], $locale);
        $subject = $isReset
            ? __('notifications.otp.reset_email_subject', ['app' => $app], $locale)
            : __('notifications.otp.login_email_subject', ['app' => $app], $locale);

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
                    'appName' => $app,
                    'codeLabel' => __('notifications.otp.code_label', [], $locale),
                ],
                'emails.auth.one-time-password-text',
            );
    }

    public function toSms(object $notifiable): string
    {
        $locale = $this->localeFor($notifiable);
        $isReset = $this->purpose === OneTimeTokenPurpose::PasswordReset;
        $key = $isReset ? 'notifications.otp.reset_sms' : 'notifications.otp.login_sms';

        return (string) __($key, [
            'code' => $this->code,
            'minutes' => $this->expiryMinutes,
        ], $locale);
    }

    /** @deprecated Use toSms() — kept for older log_sms channel callers. */
    public function toLogSms(object $notifiable): string
    {
        return $this->toSms($notifiable);
    }

    protected function localeFor(object $notifiable): string
    {
        $locale = isset($notifiable->locale) ? (string) $notifiable->locale : 'en';

        return in_array($locale, ['en', 'sw'], true) ? $locale : 'en';
    }
}
