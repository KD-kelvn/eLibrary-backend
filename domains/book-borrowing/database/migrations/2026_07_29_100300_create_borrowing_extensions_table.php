<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_borrowing.borrowing_extensions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('borrowing_request_id');
            $table->string('book_type');
            $table->unsignedBigInteger('book_type_id');
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table
                ->foreign('borrowing_request_id')
                ->references('id')
                ->on('book_borrowing.borrowing_requests')
                ->cascadeOnDelete();
            $table->index(['book_type', 'book_type_id'], 'idx_brrw_ext_book_type');
            $table->index('start_date', 'idx_brrw_ext_start_date');
            $table->index('end_date', 'idx_brrw_ext_end_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_borrowing.borrowing_extensions');
    }
};
