<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_borrowing.borrowing_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('borrowing_request_id');
            $table->unsignedBigInteger('process_id');
            $table->unsignedBigInteger('next_process_id')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status_code', 40);
            $table->unsignedBigInteger('sender_role_id')->nullable();
            $table->unsignedBigInteger('receiver_role_id')->nullable();
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('borrowing_request_id')
                ->references('id')
                ->on('book_borrowing.borrowing_requests')
                ->cascadeOnDelete();
            $table->foreign('process_id')
                ->references('id')
                ->on('book_borrowing.borrowing_processes')
                ->restrictOnDelete();
            $table->foreign('next_process_id')
                ->references('id')
                ->on('book_borrowing.borrowing_processes')
                ->nullOnDelete();
            $table->foreign('sender_role_id')->references('id')->on('auth.roles')->nullOnDelete();
            $table->foreign('receiver_role_id')->references('id')->on('auth.roles')->nullOnDelete();
            $table->index(['borrowing_request_id', 'created_at']);
            $table->index('status_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_borrowing.borrowing_progress');
    }
};
