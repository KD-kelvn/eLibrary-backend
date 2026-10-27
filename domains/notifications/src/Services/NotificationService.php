<?php

namespace Modules\Notifications\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Models\User;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Notifications\Models\DatabaseNotification;
use Modules\Notifications\Models\NotificationDelivery;
use Modules\Notifications\Models\NotificationPreference;
use Modules\Notifications\Notifications\LibraryAlertNotification;
use Modules\Notifications\Sms\SmsProviderManager;
use Modules\Settings\Services\SettingsService;

class NotificationService
{
    public function __construct(
        private readonly ChannelResolver $channels,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return list<NotificationDelivery>
     */
    public function send(
        User $user,
        NotificationCategory $category,
        string $title,
        string $body,
        array $data = [],
    ): array {
        $resolved = $this->channels->for($user, $category);

        if ($resolved === []) {
            return [];
        }

        $quietHours = app(QuietHoursGuard::class);
        $smsProvider = app(SmsProviderManager::class)->activeName();

        // Mark SMS as skipped during quiet hours (OTP bypasses this path entirely).
        if ($quietHours->isQuiet()) {
            foreach ($resolved as $index => $channel) {
                if ($channel !== NotificationChannel::Sms) {
                    continue;
                }
                NotificationDelivery::query()->create([
                    'notification_id' => null,
                    'user_id' => $user->id,
                    'category' => $category->value,
                    'channel' => $channel->value,
                    'provider' => $smsProvider,
                    'status' => 'skipped',
                    'attempts' => 0,
                    'error' => 'skipped_quiet_hours',
                ]);
                unset($resolved[$index]);
            }
            $resolved = array_values($resolved);
        }

        if ($resolved === []) {
            return [];
        }

        $notification = new LibraryAlertNotification($category, $title, $body, $data, $resolved);

        $deliveries = [];
        foreach ($resolved as $channel) {
            $deliveries[] = NotificationDelivery::query()->create([
                'notification_id' => null,
                'user_id' => $user->id,
                'category' => $category->value,
                'channel' => $channel->value,
                'provider' => $channel === NotificationChannel::Sms ? $smsProvider : $channel->provider(),
                'status' => 'queued',
                'attempts' => 0,
            ]);
        }

        $user->notify($notification);

        $latest = $user->notifications()->latest()->first();
        if ($latest) {
            NotificationDelivery::query()
                ->where('user_id', $user->id)
                ->where('category', $category->value)
                ->whereNull('notification_id')
                ->latest('id')
                ->limit(count($resolved))
                ->update([
                    'notification_id' => $latest->id,
                    'status' => 'sent',
                    'attempts' => DB::raw('attempts + 1'),
                ]);
        }

        return $deliveries;
    }

    public function listForUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $user->notifications()->paginate($perPage);
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markRead(User $user, string $notificationId): DatabaseNotification
    {
        /** @var DatabaseNotification|null $notification */
        $notification = $user->notifications()->whereKey($notificationId)->first();

        if (! $notification) {
            abort(404, 'Notification not found.');
        }

        $notification->markAsRead();

        return $notification->fresh();
    }

    public function markAllRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function preferencesFor(User $user): array
    {
        $enabled = collect($this->settings->get('notifications.channels_enabled') ?? ['in_app', 'email', 'sms'])
            ->map(fn ($value) => (string) $value)
            ->all();
        $defaults = $this->settings->get('notifications.default_preferences') ?? [];
        $stored = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->get()
            ->groupBy('category');

        $rows = [];
        foreach (NotificationCategory::cases() as $category) {
            $defaultChannels = $defaults[$category->value] ?? ['in_app', 'email', 'sms'];
            foreach (NotificationChannel::cases() as $channel) {
                if (! in_array($channel->value, $enabled, true) && $channel !== NotificationChannel::InApp) {
                    continue;
                }

                $existing = $stored->get($category->value)
                    ?->firstWhere('channel', $channel->value);

                $enabledFlag = $existing
                    ? (bool) $existing->enabled
                    : in_array($channel->value, is_array($defaultChannels) ? $defaultChannels : [], true);

                if ($channel === NotificationChannel::InApp && $category->isMandatory()) {
                    $enabledFlag = true;
                }

                $rows[] = [
                    'category' => $category->value,
                    'channel' => $channel->value,
                    'enabled' => $enabledFlag,
                    'mandatory' => $category->isMandatory() && $channel === NotificationChannel::InApp,
                    'locked' => $category->isMandatory() && $channel === NotificationChannel::InApp,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  list<array{category: string, channel: string, enabled: bool}>  $preferences
     * @return list<array<string, mixed>>
     */
    public function syncPreferences(User $user, array $preferences): array
    {
        return DB::transaction(function () use ($user, $preferences) {
            foreach ($preferences as $row) {
                $category = NotificationCategory::tryFrom($row['category'] ?? '');
                $channel = NotificationChannel::tryFrom($row['channel'] ?? '');

                if (! $category || ! $channel) {
                    continue;
                }

                $enabled = (bool) ($row['enabled'] ?? false);

                if ($category->isMandatory() && $channel === NotificationChannel::InApp) {
                    $enabled = true;
                }

                NotificationPreference::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'category' => $category->value,
                        'channel' => $channel->value,
                    ],
                    ['enabled' => $enabled],
                );
            }

            return $this->preferencesFor($user);
        });
    }
}
