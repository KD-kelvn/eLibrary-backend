<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_borrowing.penalty_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('borrowing_request_id');
            $table->morphs('book_type');
            $table->string('batch_no')->unique();
            $table->decimal('cost_per_day', 12, 2)->default(0);
            $table->string('status', 20)->default('PENDING');
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table
                ->foreign('borrowing_request_id')
                ->references('id')
                ->on('book_borrowing.borrowing_requests')
                ->cascadeOnDelete();
            $table->index('status', 'idx_brrw_pen_batch_status');
            $table->index('batch_no', 'idx_brrw_pen_batch_batch_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_borrowing.penalty_batches');
    }
};
