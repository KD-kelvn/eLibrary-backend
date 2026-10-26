<?php

namespace Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;

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
        // Sync in tests; queue workers pick up the notifications queue in production.
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
        return array_map(
            fn (NotificationChannel $channel) => $channel->driver(),
            $this->channels,
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
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->body);
    }

    public function toLogSms(object $notifiable): string
    {
        $text = trim($this->title.': '.$this->body);

        return mb_strlen($text) <= 160 ? $text : mb_substr($text, 0, 157).'...';
    }
}
