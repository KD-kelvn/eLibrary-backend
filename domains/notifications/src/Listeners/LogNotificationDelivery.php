<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Notifications\Models\NotificationDelivery;
use Modules\Notifications\Notifications\LibraryAlertNotification;
use Modules\Notifications\Sms\SmsProviderManager;
use Modules\Notifications\Sms\SmsResult;

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

        $provider = $channel->provider();
        $providerMessageId = null;
        $status = 'sent';
        $error = null;

        if ($channel === NotificationChannel::Sms) {
            $provider = app(SmsProviderManager::class)->activeName();
            if ($event->response instanceof SmsResult) {
                $provider = $event->response->provider;
                $providerMessageId = $event->response->providerMessageId;
                if (! $event->response->ok) {
                    $status = $event->response->error === 'skipped_quiet_hours' ? 'skipped' : 'failed';
                    $error = $event->response->error;
                }
            }
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
                'status' => $status,
                'attempts' => DB::raw('attempts + 1'),
                'provider' => $provider,
                'provider_message_id' => $providerMessageId,
                'error' => $error,
            ]);
    }
}
