<?php

namespace Modules\Notifications\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Authentication\Models\User;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Notifications\Models\NotificationPreference;
use Modules\Settings\Services\SettingsService;

/**
 * Resolves delivery channels = enabled ∩ (preferences ∪ mandatory rules).
 * FR-NOT-02 / TC-NOT-01: mandatory alerts always in-app + ≥1 external.
 */
class ChannelResolver
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    /**
     * @return list<NotificationChannel>
     */
    public function for(User $user, NotificationCategory $category): array
    {
        $enabled = $this->enabledChannels();
        $preferred = $this->preferredChannels($user, $category, $enabled);

        $channels = $preferred;

        if (in_array(NotificationChannel::InApp, $enabled, true) &&
                ! in_array(NotificationChannel::InApp, $channels, true)) {
            $channels[] = NotificationChannel::InApp;
        }

        if ($category->isMandatory()) {
            $channels = $this->ensureMandatoryExternal($channels, $preferred, $enabled);
        }

        return array_values(array_unique($channels, SORT_REGULAR));
    }

    /**
     * @return list<NotificationChannel>
     */
    protected function enabledChannels(): array
    {
        $raw = $this->settings->get('notifications.channels_enabled') ?? ['in_app', 'email', 'sms'];

        return collect(is_array($raw) ? $raw : [])
            ->map(fn ($value) => NotificationChannel::tryFrom((string) $value))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  list<NotificationChannel>  $enabled
     * @return list<NotificationChannel>
     */
    protected function preferredChannels(User $user, NotificationCategory $category, array $enabled): array
    {
        /** @var Collection<int, NotificationPreference> $rows */
        $rows = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('category', $category->value)
            ->get();

        if ($rows->isEmpty()) {
            $defaults = $this->settings->get('notifications.default_preferences') ?? [];
            $defaultChannels = $defaults[$category->value] ?? ['in_app', 'email', 'sms'];

            return collect(is_array($defaultChannels) ? $defaultChannels : [])
                ->map(fn ($value) => NotificationChannel::tryFrom((string) $value))
                ->filter(fn ($channel) => $channel && in_array($channel, $enabled, true))
                ->values()
                ->all();
        }

        return $rows
            ->filter(fn (NotificationPreference $row) => (bool) $row->enabled)
            ->map(fn (NotificationPreference $row) => NotificationChannel::tryFrom((string) $row->channel))
            ->filter(fn ($channel) => $channel && in_array($channel, $enabled, true))
            ->values()
            ->all();
    }

    /**
     * @param  list<NotificationChannel>  $channels
     * @param  list<NotificationChannel>  $preferred
     * @param  list<NotificationChannel>  $enabled
     * @return list<NotificationChannel>
     */
    protected function ensureMandatoryExternal(array $channels, array $preferred, array $enabled): array
    {
        $hasExternal = collect($channels)->contains(
            fn (NotificationChannel $channel) => $channel !== NotificationChannel::InApp,
        );

        if ($hasExternal) {
            return $channels;
        }

        $mandatoryExternal = collect($this->settings->get('notifications.mandatory_external') ?? ['sms', 'email'])
            ->map(fn ($value) => NotificationChannel::tryFrom((string) $value))
            ->filter(fn ($channel) => $channel && $channel !== NotificationChannel::InApp)
            ->filter(fn ($channel) => in_array($channel, $enabled, true))
            ->values();

        // Prefer channels the user still has enabled; otherwise fall back to first mandatory external.
        foreach ($mandatoryExternal as $channel) {
            if (in_array($channel, $preferred, true) || $preferred === []) {
                $channels[] = $channel;

                return array_values(array_unique($channels, SORT_REGULAR));
            }
        }

        if ($mandatoryExternal->isNotEmpty()) {
            $channels[] = $mandatoryExternal->first();
        }

        return array_values(array_unique($channels, SORT_REGULAR));
    }
}
