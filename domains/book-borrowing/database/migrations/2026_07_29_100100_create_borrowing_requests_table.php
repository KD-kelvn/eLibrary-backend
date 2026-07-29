<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_borrowing.borrowing_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('book_id');
            $table->string('book_type');
            $table->unsignedBigInteger('book_type_id');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('auth.users')->cascadeOnDelete();
            $table->foreign('book_id')->references('id')->on('book_catalog.book_details')->restrictOnDelete();
            $table->index(['book_type', 'book_type_id'], 'idx_brrw_req_book_type');
            $table->index('start_date', 'idx_brrw_req_start_date');
            $table->index('end_date', 'idx_brrw_req_end_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_borrowing.borrowing_requests');
    }
};
