<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_borrowing.stopped_penalties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('penalty_batch_id');
            $table->unsignedBigInteger('borrowing_penalty_id')->nullable();
            $table->timestamp('stopped_date');
            $table->text('stopped_reason')->nullable();
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('penalty_batch_id')
                ->references('id')
                ->on('book_borrowing.penalty_batches')
                ->cascadeOnDelete();
            $table->foreign('borrowing_penalty_id')
                ->references('id')
                ->on('book_borrowing.borrowing_penalties')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_borrowing.stopped_penalties');
    }
};
