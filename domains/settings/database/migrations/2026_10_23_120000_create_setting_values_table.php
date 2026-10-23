<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE SCHEMA IF NOT EXISTS settings');
        }

        Schema::create($this->tableName(), function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('scope'); // global | role:<code>
            $table->json('value');
            $table->timestampTz('effective_from');
            $table->foreignId('created_by')->nullable();
            $table->text('reason')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['key', 'scope', 'effective_from'], 'setting_values_key_scope_effective_idx');
            $table->index(['cancelled_at', 'effective_from'], 'setting_values_pending_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName());
    }

    protected function tableName(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'settings.setting_values'
            : 'setting_values';
    }
};
