<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * V1-003: fixed system roles cannot be deleted or renamed (FR-RBAC-01).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->table(), function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('code');
        });

        DB::table($this->table())
            ->whereIn('code', ['student', 'faculty', 'librarian', 'admin'])
            ->update(['is_system' => true]);
    }

    public function down(): void
    {
        Schema::table($this->table(), function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }

    protected function table(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'auth.roles'
            : 'roles';
    }
};
