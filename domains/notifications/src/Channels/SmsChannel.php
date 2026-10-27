<?php

namespace Modules\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Modules\Notifications\Notifications\LibraryAlertNotification;
use Modules\Notifications\Services\QuietHoursGuard;
use Modules\Notifications\Sms\SmsProviderManager;
use Modules\Notifications\Sms\SmsResult;
use RuntimeException;

/**
 * Laravel notification channel that routes through the active SmsProvider.
 * Quiet hours apply to library alerts only — OTP bypasses (decision V1-006).
 */
class SmsChannel
{
    public function __construct(
        private readonly SmsProviderManager $manager,
        private readonly QuietHoursGuard $quietHours,
    ) {}

    public function send(object $notifiable, Notification $notification): ?SmsResult
    {
        $message = $this->resolveMessage($notifiable, $notification);
        if ($message === null || $message === '') {
            return null;
        }

        if ($notification instanceof LibraryAlertNotification && $this->quietHours->isQuiet()) {
            return SmsResult::failure(
                $this->manager->activeName(),
                'skipped_quiet_hours',
            );
        }

        $to = $this->resolveRecipient($notifiable);
        if ($to === null || $to === '') {
            return SmsResult::failure($this->manager->activeName(), 'No phone number on notifiable.');
        }

        $result = $this->manager->driver()->send($to, $message);

        if (! $result->ok) {
            throw new RuntimeException(
                'SMS provider ['.$result->provider.'] failed: '.($result->error ?? 'unknown'),
            );
        }

        return $result;
    }

    protected function resolveMessage(object $notifiable, Notification $notification): ?string
    {
        if (method_exists($notification, 'toSms')) {
            return (string) $notification->toSms($notifiable);
        }

        // Backward-compatible alias used by OTP before V1-006.
        if (method_exists($notification, 'toLogSms')) {
            return (string) $notification->toLogSms($notifiable);
        }

        return null;
    }

    protected function resolveRecipient(object $notifiable): ?string
    {
        if (method_exists($notifiable, 'routeNotificationFor')) {
            $routed = $notifiable->routeNotificationFor('sms', null)
                ?? $notifiable->routeNotificationFor('log_sms', null);

            if (is_string($routed) && $routed !== '') {
                return $routed;
            }
        }

        return isset($notifiable->phone) ? (string) $notifiable->phone : null;
    }
}
