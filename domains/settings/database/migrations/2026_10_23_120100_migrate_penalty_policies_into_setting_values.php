<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed fines.grace_days / fines.rate_per_day from the active penalty_policies row.
 * Does not drop penalty_policies (deprecated for accrual; CRUD remains until a follow-up).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable($this->penaltyTable()) && ! $this->pgsqlTableExists('book_borrowing.penalty_policies')) {
            return;
        }

        if (! Schema::hasTable($this->settingsTable()) && ! $this->pgsqlTableExists('settings.setting_values')) {
            return;
        }

        $policy = DB::table($this->penaltyTable())
            ->when(
                Schema::getConnection()->getDriverName() === 'pgsql',
                fn ($q) => $q,
            )
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', now());
            })
            ->whereNull('deleted_at')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        if (! $policy) {
            return;
        }

        $roles = ['student', 'faculty', 'librarian', 'admin'];
        $now = now();

        foreach ($roles as $role) {
            $this->insertIfMissing('fines.grace_days', 'role:'.$role, (int) $policy->grace_days, $now);
            $this->insertIfMissing('fines.rate_per_day', 'role:'.$role, (float) $policy->cost_per_day, $now);
        }
    }

    public function down(): void
    {
        // Append-only history is retained; no destructive rollback of seeded rows.
    }

    protected function insertIfMissing(string $key, string $scope, mixed $value, $effectiveFrom): void
    {
        $exists = DB::table($this->settingsTable())
            ->where('key', $key)
            ->where('scope', $scope)
            ->whereNull('cancelled_at')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table($this->settingsTable())->insert([
            'key' => $key,
            'scope' => $scope,
            'value' => json_encode(['v' => $value]),
            'effective_from' => $effectiveFrom,
            'created_by' => null,
            'reason' => 'Migrated from book_borrowing.penalty_policies (V1-001)',
            'cancelled_at' => null,
            'created_at' => $effectiveFrom,
            'updated_at' => $effectiveFrom,
        ]);
    }

    protected function settingsTable(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'settings.setting_values'
            : 'setting_values';
    }

    protected function penaltyTable(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'book_borrowing.penalty_policies'
            : 'penalty_policies';
    }

    protected function pgsqlTableExists(string $schemaTable): bool
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return false;
        }

        [$schema, $table] = explode('.', $schemaTable, 2);

        return DB::table('information_schema.tables')
            ->where('table_schema', $schema)
            ->where('table_name', $table)
            ->exists();
    }
};
