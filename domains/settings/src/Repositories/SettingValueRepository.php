<?php

namespace Modules\Settings\Repositories;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Settings\Models\SettingValue;

class SettingValueRepository
{
    public function __construct(protected SettingValue $model) {}

    /**
     * Insert a new setting row (append-only).
     *
     * @param  array{key: string, scope: string, value: mixed, effective_from: CarbonInterface|string, created_by?: int|null, reason?: string|null}  $attributes
     */
    public function insert(array $attributes): SettingValue
    {
        return $this->model->newQuery()->create([
            'key' => $attributes['key'],
            'scope' => $attributes['scope'],
            'value' => ['v' => $attributes['value']],
            'effective_from' => $attributes['effective_from'],
            'created_by' => $attributes['created_by'] ?? null,
            'reason' => $attributes['reason'] ?? null,
            'cancelled_at' => null,
        ]);
    }

    public function cancel(SettingValue $row, ?string $reason = null): SettingValue
    {
        $row->forceFill([
            'cancelled_at' => now(),
            'reason' => $reason ?? $row->reason,
        ])->save();

        return $row->refresh();
    }

    public function find(int $id): ?SettingValue
    {
        return $this->model->newQuery()->find($id);
    }

    /**
     * Latest non-cancelled row for key/scope with effective_from <= $at.
     */
    public function effectiveAt(string $key, string $scope, CarbonInterface|string|null $at = null): ?SettingValue
    {
        $moment = Carbon::parse($at ?? now());

        return $this->model->newQuery()
            ->where('key', $key)
            ->where('scope', $scope)
            ->whereNull('cancelled_at')
            ->where('effective_from', '<=', $moment)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Next future non-cancelled effective_from after $at (any key/scope).
     */
    public function nextPendingEffectiveFrom(CarbonInterface|string|null $at = null): ?CarbonInterface
    {
        $moment = Carbon::parse($at ?? now());

        $value = $this->model->newQuery()
            ->whereNull('cancelled_at')
            ->where('effective_from', '>', $moment)
            ->orderBy('effective_from')
            ->value('effective_from');

        return $value ? Carbon::parse($value) : null;
    }

    /**
     * Pending rows whose effective_from has arrived (for activate-pending).
     *
     * @return Collection<int, SettingValue>
     */
    public function duePending(CarbonInterface|string|null $at = null): Collection
    {
        $moment = Carbon::parse($at ?? now());

        return $this->model->newQuery()
            ->whereNull('cancelled_at')
            ->where('effective_from', '<=', $moment)
            ->where('effective_from', '>', $moment->copy()->subMinutes(2))
            ->orderBy('effective_from')
            ->get();
    }

    /**
     * Whether any non-cancelled future-dated row exists with effective_from <= now
     * that might not yet be reflected in a stale cache (used by activate-pending).
     */
    public function hasNewlyEffective(CarbonInterface|string|null $since, CarbonInterface|string|null $at = null): bool
    {
        $moment = Carbon::parse($at ?? now());
        $sinceAt = Carbon::parse($since);

        return $this->model->newQuery()
            ->whereNull('cancelled_at')
            ->where('effective_from', '>', $sinceAt)
            ->where('effective_from', '<=', $moment)
            ->exists();
    }
}
