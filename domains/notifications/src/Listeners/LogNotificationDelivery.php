<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Notifications\Models\NotificationDelivery;
use Modules\Notifications\Notifications\LibraryAlertNotification;

class LogNotificationDelivery
{
    public function handle(NotificationSent $event): void
    {
        if (! $event->notification instanceof LibraryAlertNotification) {
            return;
        }

        $channel = NotificationChannel::tryFromDriver($event->channel);
        if (! $channel) {
            return;
        }

        $notifiable = $event->notifiable;
        $userId = is_object($notifiable) && isset($notifiable->id) ? (int) $notifiable->id : null;
        if (! $userId) {
            return;
        }

        NotificationDelivery::query()
            ->where('user_id', $userId)
            ->where('category', $event->notification->category->value)
            ->where('channel', $channel->value)
            ->where(function ($query) {
                $query->whereNull('notification_id')->orWhere('status', 'queued');
            })
            ->latest('id')
            ->limit(1)
            ->update([
                'status' => 'sent',
                'attempts' => DB::raw('attempts + 1'),
                'provider' => $channel->provider(),
            ]);
    }
}
