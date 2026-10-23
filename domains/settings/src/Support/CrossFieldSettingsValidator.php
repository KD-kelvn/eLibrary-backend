<?php

namespace Modules\Settings\Support;

/**
 * Library policy §8 cross-field rules for batch updates.
 */
class CrossFieldSettingsValidator
{
    public function __construct(protected SettingsRegistry $registry) {}

    /**
     * @param  array<string, mixed>  $proposed  map of "key|scope" => value (after merge with current)
     * @return list<array{rule: string, message: string}>
     */
    public function violations(array $proposed): array
    {
        $errors = [];

        foreach ($this->registry->crossFieldRules() as $rule) {
            $name = $rule['rule'] ?? '';
            $message = $rule['message'] ?? $name;

            $ok = match ($name) {
                'cap_gte_rate' => $this->capGteRate($proposed),
                'due_before_lt_period' => $this->dueBeforeLtPeriod($proposed),
                'mandatory_subset_channels' => $this->mandatorySubsetChannels($proposed),
                'staff_idle_max' => $this->staffIdleMax($proposed),
                default => true,
            };

            if (! $ok) {
                $errors[] = ['rule' => $name, 'message' => $message];
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $proposed
     */
    protected function capGteRate(array $proposed): bool
    {
        foreach (['student', 'faculty', 'librarian', 'admin'] as $role) {
            $cap = $proposed['fines.cap_per_loan|role:'.$role] ?? null;
            $rate = $proposed['fines.rate_per_day|role:'.$role] ?? null;

            if ($cap === null || $rate === null) {
                continue;
            }

            if ((float) $cap !== 0.0 && (float) $cap < (float) $rate) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $proposed
     */
    protected function dueBeforeLtPeriod(array $proposed): bool
    {
        $due = $proposed['reminders.due_before_days|global'] ?? null;
        if ($due === null) {
            return true;
        }

        $minPeriod = null;
        foreach (['student', 'faculty', 'librarian', 'admin'] as $role) {
            $period = $proposed['loans.period_days|role:'.$role] ?? null;
            if ($period === null) {
                continue;
            }
            $minPeriod = $minPeriod === null ? (int) $period : min($minPeriod, (int) $period);
        }

        if ($minPeriod === null) {
            return true;
        }

        return (int) $due < $minPeriod;
    }

    /**
     * @param  array<string, mixed>  $proposed
     */
    protected function mandatorySubsetChannels(array $proposed): bool
    {
        $mandatory = $proposed['notifications.mandatory_external|global'] ?? null;
        $enabled = $proposed['notifications.channels_enabled|global'] ?? null;

        if (! is_array($mandatory) || ! is_array($enabled)) {
            return true;
        }

        foreach ($mandatory as $channel) {
            if (! in_array($channel, $enabled, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $proposed
     */
    protected function staffIdleMax(array $proposed): bool
    {
        foreach (['faculty', 'librarian', 'admin'] as $role) {
            $idle = $proposed['sessions.idle_timeout_min|role:'.$role] ?? null;
            if ($idle !== null && (int) $idle > 480) {
                return false;
            }
        }

        return true;
    }
}
