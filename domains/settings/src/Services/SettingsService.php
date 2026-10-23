<?php

namespace Modules\Settings\Services;

use App\Support\AdminActivity;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Settings\Models\SettingValue;
use Modules\Settings\Repositories\SettingValueRepository;
use Modules\Settings\Support\PolicySnapshot;
use Modules\Settings\Support\SettingsCache;
use Modules\Settings\Support\SettingsRegistry;

class SettingsService
{
    public function __construct(
        protected SettingsRegistry $registry,
        protected SettingValueRepository $repository,
        protected SettingsCache $cache,
    ) {}

    /**
     * Effective value for a key at time $at.
     *
     * @param  string|null  $roleCode  Required for role-scoped keys (defaults to student).
     */
    public function get(string $key, ?string $roleCode = null, CarbonInterface|string|null $at = null): mixed
    {
        $this->registry->definition($key);
        $scope = $this->registry->normalizeScope($key, $roleCode);

        // Point-in-time reads bypass the "now" cache map.
        if ($at !== null) {
            return $this->resolveFromStore($key, $scope, Carbon::parse($at));
        }

        $map = $this->cachedMap();
        $cacheKey = $key.'|'.$scope;

        if (array_key_exists($cacheKey, $map['values'])) {
            return $map['values'][$cacheKey];
        }

        return $this->resolveFromStore($key, $scope, now());
    }

    public function forRole(string $roleCode, CarbonInterface|string|null $at = null): PolicySnapshot
    {
        return new PolicySnapshot(
            roleCode: $roleCode,
            loanPeriodDays: (int) $this->get('loans.period_days', $roleCode, $at),
            maxConcurrentLoans: (int) $this->get('loans.max_concurrent', $roleCode, $at),
            maxRenewals: (int) $this->get('renewals.max_per_loan', $roleCode, $at),
            fineRatePerDay: (float) $this->get('fines.rate_per_day', $roleCode, $at),
            graceDays: (int) $this->get('fines.grace_days', $roleCode, $at),
            fineCapPerLoan: (float) $this->get('fines.cap_per_loan', $roleCode, $at),
            blockThreshold: (float) $this->get('fines.block_threshold', $roleCode, $at),
            pickupWindowDays: (int) $this->get('requests.pickup_window_days', null, $at),
            holdCollectWindowDays: (int) $this->get('holds.collect_window_days', null, $at),
            maxActiveHolds: (int) $this->get('holds.max_active', $roleCode, $at),
        );
    }

    /**
     * Append a new setting value (FR-SET-04).
     */
    public function change(
        string $key,
        mixed $value,
        string $reason,
        ?string $roleCode = null,
        CarbonInterface|string|null $effectiveFrom = null,
        ?int $createdBy = null,
    ): SettingValue {
        $this->registry->definition($key);
        $scope = $this->registry->normalizeScope($key, $roleCode);
        $from = Carbon::parse($effectiveFrom ?? now());
        $old = $this->get($key, $roleCode);

        $row = DB::transaction(function () use ($key, $scope, $value, $reason, $from, $createdBy) {
            return $this->repository->insert([
                'key' => $key,
                'scope' => $scope,
                'value' => $value,
                'effective_from' => $from,
                'created_by' => $createdBy ?? auth()->id(),
                'reason' => $reason,
            ]);
        });

        $this->cache->bump();

        AdminActivity::log(
            'Library setting changed',
            $row,
            [
                'key' => $key,
                'scope' => $scope,
                'old' => $old,
                'new' => $value,
                'reason' => $reason,
                'effective_from' => $from->toIso8601String(),
            ],
            logName: 'settings',
            event: 'settings.changed',
        );

        return $row;
    }

    /**
     * Write registry defaults for the given keys (reset).
     *
     * @param  list<string>  $keys
     * @return list<SettingValue>
     */
    public function reset(
        array $keys,
        string $reason,
        ?string $roleCode = null,
        CarbonInterface|string|null $effectiveFrom = null,
        ?int $createdBy = null,
    ): array {
        $rows = [];

        foreach ($keys as $key) {
            $default = $this->registry->default($key, $roleCode);
            $rows[] = $this->change(
                $key,
                $default,
                $reason,
                $roleCode,
                $effectiveFrom,
                $createdBy,
            );
        }

        AdminActivity::log(
            'Library settings reset',
            null,
            [
                'keys' => $keys,
                'role' => $roleCode,
                'reason' => $reason,
            ],
            logName: 'settings',
            event: 'settings.reset',
        );

        return $rows;
    }

    /**
     * Cancel a future (or mistaken) row by setting cancelled_at only.
     */
    public function cancel(int $id, string $reason): SettingValue
    {
        $row = $this->repository->find($id);

        if (! $row) {
            throw new InvalidArgumentException("Setting value [{$id}] not found.");
        }

        if ($row->cancelled_at !== null) {
            return $row;
        }

        $cancelled = $this->repository->cancel($row, $reason);
        $this->cache->bump();

        AdminActivity::log(
            'Library setting change cancelled',
            $cancelled,
            [
                'key' => $cancelled->key,
                'scope' => $cancelled->scope,
                'reason' => $reason,
                'effective_from' => optional($cancelled->effective_from)?->toIso8601String(),
            ],
            logName: 'settings',
            event: 'settings.cancelled',
        );

        return $cancelled;
    }

    /**
     * Bump cache when pending rows become effective (FR-SET-09).
     *
     * @return int Number of version bumps performed (0 or 1)
     */
    public function activatePending(CarbonInterface|string|null $at = null): int
    {
        $moment = Carbon::parse($at ?? now());
        $since = $this->cache->lastActivatedAt() ?? $moment->copy()->subMinute();

        if (! $this->repository->hasNewlyEffective($since, $moment)) {
            $this->cache->markActivatedAt($moment);

            return 0;
        }

        $this->cache->bump();
        $this->cache->markActivatedAt($moment);

        return 1;
    }

    /**
     * @return array{values: array<string, mixed>, valid_until: string|null}
     */
    protected function cachedMap(): array
    {
        return $this->cache->remember(function () {
            $values = [];
            $now = now();

            foreach ($this->registry->all() as $key => $definition) {
                if (($definition['scope'] ?? 'global') === 'role') {
                    foreach (array_keys($definition['default'] ?? []) as $role) {
                        $scope = 'role:'.$role;
                        $values[$key.'|'.$scope] = $this->resolveFromStore($key, $scope, $now);
                    }
                } else {
                    $values[$key.'|global'] = $this->resolveFromStore($key, 'global', $now);
                }
            }

            $next = $this->repository->nextPendingEffectiveFrom($now);

            return [
                'values' => $values,
                'valid_until' => $next?->toIso8601String(),
            ];
        });
    }

    protected function resolveFromStore(string $key, string $scope, CarbonInterface $at): mixed
    {
        $row = $this->repository->effectiveAt($key, $scope, $at);

        if ($row) {
            return $row->decodedValue();
        }

        $role = $this->registry->roleFromScope($scope);

        return $this->registry->default($key, $role);
    }
}
