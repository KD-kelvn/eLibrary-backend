<?php

namespace Modules\Notifications\Notifications;

use App\Support\Mail\BrandedMailMessage;
use App\Support\Mail\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Notifications\Services\QuietHoursGuard;

class LibraryAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<NotificationChannel>  $channels
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly NotificationCategory $category,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
        public readonly array $channels = [],
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Retry with backoff (FR-NOT-04): 1, 5, 15 minutes.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function tries(): int
    {
        return 3;
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = $this->channels;

        if (app(QuietHoursGuard::class)->isQuiet()) {
            $channels = array_values(array_filter(
                $channels,
                fn (NotificationChannel $channel) => $channel !== NotificationChannel::Sms,
            ));
        }

        return array_map(
            fn (NotificationChannel $channel) => $channel->driver(),
            $channels,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => $this->category->value,
            'title' => $this->title,
            'body' => $this->body,
            'mandatory' => $this->category->isMandatory(),
            ...$this->data,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $this->localeFor($notifiable);
        $branding = app(MailBranding::class)->resolve();

        $greetingName = $notifiable->username ?? null;
        if (method_exists($notifiable, 'relationLoaded') && method_exists($notifiable, 'profile')) {
            try {
                $notifiable->loadMissing('profile');
                $greetingName = $notifiable->profile?->fullname ?? $greetingName;
            } catch (\Throwable) {
                // Profile table may be absent in focused feature tests.
            }
        }

        $preheader = __('notifications.library_alert.email_preheader', [
            'title' => $this->title,
            'body' => $this->body,
        ], $locale);

        $actionUrl = isset($this->data['action_url']) ? (string) $this->data['action_url'] : null;
        $actionText = isset($this->data['action_text'])
            ? (string) $this->data['action_text']
            : $branding['app_name'];

        return BrandedMailMessage::make($this->title)
            ->brandedView(
                'emails.notifications.library-alert',
                [
                    'title' => $this->title,
                    'body' => $this->body,
                    'greetingName' => $greetingName,
                    'preheader' => $preheader,
                    'actionUrl' => $actionUrl,
                    'actionText' => $actionText,
                    'appName' => $branding['app_name'],
                ],
                'emails.notifications.library-alert-text',
            );
    }

    public function toSms(object $notifiable): string
    {
        $locale = $this->localeFor($notifiable);
        $text = __('notifications.library_alert.sms', [
            'title' => $this->title,
            'body' => $this->body,
        ], $locale);

        $text = trim((string) $text);

        return mb_strlen($text) <= 160 ? $text : mb_substr($text, 0, 157).'...';
    }

    /** @deprecated Use toSms() */
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
