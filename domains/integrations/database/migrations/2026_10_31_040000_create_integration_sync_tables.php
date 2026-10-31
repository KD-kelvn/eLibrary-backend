<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * V1-023 / FR-SYNC-01/05 — sync_runs + sync_run_items.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE SCHEMA IF NOT EXISTS integration');
        }

        $runs = $this->runsTable();
        $items = $this->itemsTable();

        if (! Schema::hasTable($runs)) {
            Schema::create($runs, function (Blueprint $table) {
                $table->id();
                $table->string('mode', 20); // push|pull
                $table->string('status', 20)->default('queued'); // queued|running|completed|failed
                $table->string('idempotency_key')->nullable();
                $table->string('cursor')->nullable();
                $table->unsignedInteger('created_count')->default(0);
                $table->unsignedInteger('updated_count')->default(0);
                $table->unsignedInteger('deactivated_count')->default(0);
                $table->unsignedInteger('unchanged_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->unsignedBigInteger('triggered_by')->nullable();
                $table->text('error_summary')->nullable();
                $table->timestampTz('started_at')->nullable();
                $table->timestampTz('finished_at')->nullable();
                $table->timestamps();

                $table->unique('idempotency_key');
                $table->index(['mode', 'status']);
                $table->index('started_at');
            });
        }

        if (! Schema::hasTable($items)) {
            Schema::create($items, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sync_run_id');
                $table->string('reg_no');
                $table->string('action', 20); // created|updated|deactivated|unchanged|failed|skipped
                $table->unsignedBigInteger('user_id')->nullable();
                $table->text('error')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index('sync_run_id');
                $table->index('reg_no');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists($this->itemsTable());
        Schema::dropIfExists($this->runsTable());
    }

    private function runsTable(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'integration.sync_runs'
            : 'sync_runs';
    }

    private function itemsTable(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'integration.sync_run_items'
            : 'sync_run_items';
    }
};
