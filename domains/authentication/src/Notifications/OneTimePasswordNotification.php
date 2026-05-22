<?php

namespace Modules\Authentication\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Authentication\Enums\OneTimeTokenVia;

class OneTimePasswordNotification extends Notification
{
    public function __construct(
        private readonly string $code,
        private readonly OneTimeTokenVia $via,
        private readonly int $expiryMinutes,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return match ($this->via) {
            OneTimeTokenVia::Email => ['mail'],
            OneTimeTokenVia::Phone => ['log_sms'],
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your login code')
            ->line("Your one-time login code is: **{$this->code}**")
            ->line("This code expires in {$this->expiryMinutes} minutes.")
            ->line('If you did not request this code, you can ignore this email.');
    }

    public function toLogSms(object $notifiable): string
    {
        return "Your login code is {$this->code}. It expires in {$this->expiryMinutes} minutes.";
    }
}
