<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * V1-020 / FR-USR-01: first / middle / last name (fullname kept as display/search).
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = $this->tableName();

        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            if (! Schema::hasColumn($table, 'first_name')) {
                $blueprint->string('first_name')->nullable()->after('fullname');
            }
            if (! Schema::hasColumn($table, 'middle_name')) {
                $blueprint->string('middle_name')->nullable()->after('first_name');
            }
            if (! Schema::hasColumn($table, 'last_name')) {
                $blueprint->string('last_name')->nullable()->after('middle_name');
            }
        });

        $this->backfillFromFullname($table);
    }

    public function down(): void
    {
        $table = $this->tableName();

        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            $columns = collect(['first_name', 'middle_name', 'last_name'])
                ->filter(fn (string $column) => Schema::hasColumn($table, $column))
                ->all();

            if ($columns !== []) {
                $blueprint->dropColumn($columns);
            }
        });
    }

    private function backfillFromFullname(string $table): void
    {
        $rows = DB::table($table)
            ->select(['id', 'fullname', 'first_name', 'last_name'])
            ->whereNull('first_name')
            ->whereNotNull('fullname')
            ->get();

        foreach ($rows as $row) {
            $parts = preg_split('/\s+/', trim((string) $row->fullname), 3) ?: [];
            $first = $parts[0] ?? '';
            $middle = count($parts) === 3 ? ($parts[1] ?? null) : null;
            $last = count($parts) >= 2
                ? ($parts[count($parts) === 3 ? 2 : 1] ?? null)
                : null;

            if ($first === '') {
                continue;
            }

            DB::table($table)->where('id', $row->id)->update([
                'first_name' => $first,
                'middle_name' => $middle,
                'last_name' => $last,
            ]);
        }
    }

    private function tableName(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'auth.user_profiles'
            : 'user_profiles';
    }
};
