<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V1-022 / FR-USR-03/04 — bulk user import preview + commit.
 */
return new class extends Migration
{
    public function up(): void
    {
        $imports = $this->importsTable();
        $rows = $this->rowsTable();

        if (! Schema::hasTable($imports)) {
            Schema::create($imports, function (Blueprint $table) {
                $table->id();
                $table->string('type', 20); // student|staff
                $table->string('status', 20)->default('preview'); // preview|committed
                $table->string('original_filename');
                $table->string('mime')->nullable();
                $table->string('storage_path')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamp('committed_at')->nullable();
                $table->json('summary')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable($rows)) {
            Schema::create($rows, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_import_id');
                $table->unsignedInteger('row_number');
                $table->json('payload');
                $table->string('status', 20)->default('valid'); // valid|invalid|created|updated|unchanged
                $table->json('errors')->nullable();
                $table->unsignedBigInteger('matched_user_id')->nullable();
                $table->timestamps();

                $table->unique(['user_import_id', 'row_number']);
                $table->index('user_import_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists($this->rowsTable());
        Schema::dropIfExists($this->importsTable());
    }

    private function importsTable(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'auth.user_imports'
            : 'user_imports';
    }

    private function rowsTable(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'auth.user_import_rows'
            : 'user_import_rows';
    }
};
