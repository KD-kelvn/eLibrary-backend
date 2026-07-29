<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_borrowing.penalty_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('grace_days')->default(0);
            $table->decimal('cost_per_day', 12, 2)->default(0);
            $table->string('currency', 10)->default('TZS');
            $table->boolean('is_active')->default(false);
            $table->timestamp('effective_from')->nullable();
            $table->text('description')->nullable();
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active', 'idx_brrw_pen_policy_active');
            $table->index('effective_from', 'idx_brrw_pen_policy_effective');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_borrowing.penalty_policies');
    }
};
