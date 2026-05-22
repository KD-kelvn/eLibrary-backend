<?php

namespace Modules\Authentication\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Free dev SMS channel — writes OTPs to the application log.
 * Swap for Twilio/Vonage when you move to production.
 */
class LogSmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toLogSms')) {
            return;
        }

        $message = $notification->toLogSms($notifiable);

        Log::info('SMS OTP (development)', [
            'to' => $notifiable->phone ?? $notifiable->routeNotificationForLogSms(),
            'message' => $message,
        ]);
    }
}
