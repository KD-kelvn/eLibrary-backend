<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = $this->tableName();

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->boolean('is_active')->default(true);
            $blueprint->text('deactivated_reason')->nullable();
            $blueprint->boolean('break_glass')->default(false);
            $blueprint->timestampTz('locked_until')->nullable();
            $blueprint->string('locale', 10)->default('en');
        });

        // Username / phone uniqueness (phone nullable — multiple NULLs allowed on Postgres).
        Schema::table($table, function (Blueprint $table) {
            $table->unique('username');
            $table->unique('phone');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX IF NOT EXISTS users_is_active_index ON '.$table.' (is_active)');
            DB::statement('CREATE INDEX IF NOT EXISTS users_locked_until_index ON '.$table.' (locked_until)');
        } else {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->index('is_active');
                $blueprint->index('locked_until');
            });
        }
    }

    public function down(): void
    {
        $table = $this->tableName();

        Schema::table($table, function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropUnique(['phone']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['locked_until']);
            $table->dropColumn([
                'is_active',
                'deactivated_reason',
                'break_glass',
                'locked_until',
                'locale',
            ]);
        });
    }

    protected function tableName(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'auth.users'
            : 'users';
    }
};
