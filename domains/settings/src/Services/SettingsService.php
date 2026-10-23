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
     * Full admin catalog: current value, default, rules, pending per key/scope.
     *
     * @return list<array<string, mixed>>
     */
    public function catalog(?string $group = null): array
    {
        $entries = [];

        foreach ($this->registry->all() as $key => $definition) {
            if ($group !== null && ($definition['group'] ?? null) !== $group) {
                continue;
            }

            if (($definition['scope'] ?? 'global') === 'role') {
                foreach (array_keys($definition['default'] ?? []) as $role) {
                    $entries[] = $this->catalogEntry($key, $definition, 'role:'.$role, $role);
                }
            } else {
                $entries[] = $this->catalogEntry($key, $definition, 'global', null);
            }
        }

        return $entries;
    }

    /**
     * Non-sensitive public subset for SPA (SDD §12.4).
     *
     * @return list<array<string, mixed>>
     */
    public function publicCatalog(): array
    {
        $publicKeys = config('library-settings.public_keys', [
            'files.book_types',
            'files.book_max_mb',
            'files.image_types',
            'files.image_max_mb',
            'otp.length',
            'loans.period_days',
            'fines.grace_days',
            'fines.rate_per_day',
        ]);

        return collect($this->catalog())
            ->filter(fn (array $row) => in_array($row['key'], $publicKeys, true))
            ->values()
            ->map(fn (array $row) => [
                'key' => $row['key'],
                'scope' => $row['scope'],
                'value' => $row['value'],
                'default' => $row['default'],
                'label' => $row['label'] ?? null,
            ])
            ->all();
    }

    /**
     * Atomic batch change (FR-SET-02).
     *
     * @param  list<array{key: string, scope: string, value: mixed}>  $changes
     * @return list<SettingValue>
     */
    public function changeBatch(
        array $changes,
        string $reason,
        CarbonInterface|string|null $effectiveFrom = null,
        ?int $createdBy = null,
    ): array {
        return DB::transaction(function () use ($changes, $reason, $effectiveFrom, $createdBy) {
            $rows = [];

            foreach ($changes as $change) {
                $role = $this->registry->roleFromScope($change['scope']);
                $rows[] = $this->change(
                    $change['key'],
                    $change['value'],
                    $reason,
                    $role,
                    $effectiveFrom,
                    $createdBy,
                );
            }

            return $rows;
        });
    }

    /**
     * Reset keys, a group, or all registry keys (FR-SET-06).
     *
     * @param  list<string>|null  $keys
     * @return list<SettingValue>
     */
    public function resetRequest(
        string $reason,
        ?array $keys = null,
        ?string $group = null,
        bool $all = false,
        ?string $roleCode = null,
        CarbonInterface|string|null $effectiveFrom = null,
        ?int $createdBy = null,
    ): array {
        $targetKeys = match (true) {
            $all => array_keys($this->registry->all()),
            filled($group) => collect($this->registry->all())
                ->filter(fn (array $def) => ($def['group'] ?? null) === $group)
                ->keys()
                ->all(),
            default => $keys ?? [],
        };

        if ($targetKeys === []) {
            throw new InvalidArgumentException('No settings selected for reset.');
        }

        $rows = [];

        foreach ($targetKeys as $key) {
            if ($this->registry->isRoleScoped($key)) {
                $roles = $roleCode
                    ? [$roleCode]
                    : array_keys($this->registry->definition($key)['default'] ?? []);

                foreach ($roles as $role) {
                    $rows = array_merge($rows, $this->reset(
                        [$key],
                        $reason,
                        $role,
                        $effectiveFrom,
                        $createdBy,
                    ));
                }
            } else {
                $rows = array_merge($rows, $this->reset(
                    [$key],
                    $reason,
                    null,
                    $effectiveFrom,
                    $createdBy,
                ));
            }
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function history(?string $key = null, ?string $scope = null, CarbonInterface|string|null $from = null, CarbonInterface|string|null $to = null): array
    {
        return $this->repository->history($key, $scope, $from, $to)
            ->map(function (SettingValue $row) {
                return [
                    'id' => $row->id,
                    'key' => $row->key,
                    'scope' => $row->scope,
                    'value' => $row->decodedValue(),
                    'reason' => $row->reason,
                    'effectiveFrom' => optional($row->effective_from)?->toIso8601String(),
                    'cancelledAt' => optional($row->cancelled_at)?->toIso8601String(),
                    'createdBy' => $row->created_by,
                    'createdAt' => optional($row->created_at)?->toIso8601String(),
                ];
            })
            ->all();
    }

    /**
     * Current effective map for cross-field validation (key|scope => value).
     *
     * @return array<string, mixed>
     */
    public function effectiveMap(): array
    {
        return $this->cachedMap()['values'];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    protected function catalogEntry(string $key, array $definition, string $scope, ?string $role): array
    {
        $pending = $this->repository->pendingFor($key, $scope)->map(fn (SettingValue $row) => [
            'id' => $row->id,
            'value' => $row->decodedValue(),
            'effectiveFrom' => optional($row->effective_from)?->toIso8601String(),
            'reason' => $row->reason,
        ])->values()->all();

        return [
            'key' => $key,
            'scope' => $scope,
            'group' => $definition['group'] ?? null,
            'type' => $definition['type'] ?? null,
            'value' => $this->get($key, $role),
            'default' => $this->registry->default($key, $role),
            'rules' => $this->registry->rules($key),
            'min' => $definition['min'] ?? null,
            'max' => $definition['max'] ?? null,
            'allowed' => $definition['allowed'] ?? null,
            'snapshot' => (bool) ($definition['snapshot'] ?? false),
            'label' => $definition['label'] ?? null,
            'pending' => $pending,
        ];
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
