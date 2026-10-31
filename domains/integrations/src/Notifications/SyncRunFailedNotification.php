<?php

namespace Modules\Integrations\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Integrations\Models\SyncRun;

class SyncRunFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly SyncRun $run) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('University sync run needs attention')
            ->line("Sync run #{$this->run->id} ({$this->run->mode}) finished with {$this->run->failed_count} failed record(s).")
            ->line($this->run->error_summary ?? 'See sync run details in admin.')
            ->action('View sync runs', url('/admin/access/sync-runs/'.$this->run->id));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'sync_run_id' => $this->run->id,
            'mode' => $this->run->mode,
            'failed_count' => $this->run->failed_count,
            'error_summary' => $this->run->error_summary,
        ];
    }
}
