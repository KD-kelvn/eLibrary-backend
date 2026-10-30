<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V1-011: challenge-scoped OTP rows with attempt counters and policy snapshots.
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
            if (! Schema::hasColumn($table, 'challenge_id')) {
                $blueprint->uuid('challenge_id')->nullable()->after('user_id')->index();
            }
            if (! Schema::hasColumn($table, 'attempts')) {
                $blueprint->unsignedSmallInteger('attempts')->default(0)->after('used_at');
            }
            if (! Schema::hasColumn($table, 'max_attempts')) {
                $blueprint->unsignedSmallInteger('max_attempts')->nullable()->after('attempts');
            }
            if (! Schema::hasColumn($table, 'code_length')) {
                $blueprint->unsignedTinyInteger('code_length')->nullable()->after('max_attempts');
            }
            if (! Schema::hasColumn($table, 'expiry_minutes')) {
                $blueprint->unsignedTinyInteger('expiry_minutes')->nullable()->after('code_length');
            }
        });
    }

    public function down(): void
    {
        $table = $this->tableName();

        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            $columns = collect([
                'challenge_id',
                'attempts',
                'max_attempts',
                'code_length',
                'expiry_minutes',
            ])->filter(fn (string $column) => Schema::hasColumn($table, $column))->all();

            if ($columns !== []) {
                $blueprint->dropColumn($columns);
            }
        });
    }

    private function tableName(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'auth.one_time_tokens'
            : 'one_time_tokens';
    }
};
