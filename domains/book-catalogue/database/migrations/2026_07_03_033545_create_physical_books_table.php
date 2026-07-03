<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_catalog.physical_books', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_detail_id');
            $table->unsignedBigInteger('shelf_id');
            $table->string('code_no')->unique();
            $table->integer('copies');
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('book_detail_id')->references('id')->on('book_catalog.book_details')->onDelete('cascade');
            $table->foreign('shelf_id')->references('id')->on('book_catalog.shelves')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_catalog.physical_books');
    }
};
