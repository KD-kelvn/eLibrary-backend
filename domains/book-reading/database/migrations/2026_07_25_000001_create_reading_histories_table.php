<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_catalog.reading_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('book_detail_id');
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->string('current_location')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('device')->nullable();
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('auth.users')->cascadeOnDelete();
            $table->foreign('book_detail_id')->references('id')->on('book_catalog.book_details')->cascadeOnDelete();
            $table->index(['user_id', 'last_read_at']);
            $table->index(['book_detail_id', 'last_read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_catalog.reading_histories');
    }
};
