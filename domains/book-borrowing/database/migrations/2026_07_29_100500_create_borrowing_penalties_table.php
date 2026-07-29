<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_borrowing.borrowing_penalties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('penalty_batch_id');
            $table->unsignedBigInteger('borrowing_request_id');
            $table->morphs('book_type');
            $table->date('penalty_date');
            $table->string('bill_no')->nullable();
            $table->string('control_no')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table
                ->foreign('penalty_batch_id')
                ->references('id')
                ->on('book_borrowing.penalty_batches')
                ->cascadeOnDelete();
            $table
                ->foreign('borrowing_request_id')
                ->references('id')
                ->on('book_borrowing.borrowing_requests')
                ->cascadeOnDelete();
            $table->index('penalty_date', 'idx_brrw_pen_penalty_date');
            $table->index('is_paid', 'idx_brrw_pen_is_paid');
            $table->index('bill_no', 'idx_brrw_pen_bill_no');
            $table->index('control_no', 'idx_brrw_pen_control_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_borrowing.borrowing_penalties');
    }
};
