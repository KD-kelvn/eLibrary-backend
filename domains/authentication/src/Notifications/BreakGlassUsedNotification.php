<?php

namespace Modules\Authentication\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Authentication\Models\User;

class BreakGlassUsedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly User $actor,
        private readonly string $occurredAt,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $who = $this->actor->username ?: $this->actor->email;

        return (new MailMessage)
            ->subject('Break-glass sign-in used')
            ->line("A break-glass admin sign-in completed for {$who}.")
            ->line('Time: '.$this->occurredAt)
            ->line('If this was unexpected, revoke sessions and review audit logs.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'auth.break_glass',
            'actor_id' => $this->actor->id,
            'actor_username' => $this->actor->username,
            'occurred_at' => $this->occurredAt,
        ];
    }
}
