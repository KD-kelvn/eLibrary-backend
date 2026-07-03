<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_catalog.digital_books', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_detail_id');
            $table->string('file_cover')->nullable();
            // eBook asset (from FilesRepository)
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->string('format', 20);  // epub, pdf, mobi

            $table->boolean('is_downloadable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('checksum', 64)->nullable();
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('book_detail_id')->references('id')->on('book_catalog.book_details')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_catalog.digital_books');
    }
};
