<?php

namespace Modules\Notifications\Services;

use Illuminate\Support\Carbon;
use Modules\Settings\Services\SettingsService;

/**
 * LP-N4 — library SMS is suppressed during quiet hours; OTP always allowed.
 */
class QuietHoursGuard
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function isQuiet(?Carbon $at = null): bool
    {
        $config = $this->settings->get('sms.quiet_hours') ?? [];
        if (! is_array($config) || ! ($config['enabled'] ?? false)) {
            return false;
        }

        $start = (string) ($config['start'] ?? '21:00');
        $end = (string) ($config['end'] ?? '07:00');
        $now = $at?->copy() ?? now();

        return $this->fallsInRange($now, $start, $end);
    }

    /**
     * Inclusive start, exclusive end. Overnight ranges (start > end) wrap midnight.
     */
    protected function fallsInRange(Carbon $now, string $start, string $end): bool
    {
        $current = $now->format('H:i');

        if ($start === $end) {
            return false;
        }

        if ($start < $end) {
            return $current >= $start && $current < $end;
        }

        // Overnight e.g. 21:00–07:00
        return $current >= $start || $current < $end;
    }
}
