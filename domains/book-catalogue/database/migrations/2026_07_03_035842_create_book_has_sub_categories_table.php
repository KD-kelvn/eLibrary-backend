<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_catalog.book_has_sub_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_detail_id');
            $table->unsignedBigInteger('sub_category_id');
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('book_detail_id')->references('id')->on('book_catalog.book_details')->onDelete('cascade');
            $table->foreign('sub_category_id')->references('id')->on('book_catalog.sub_categories')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_catalog.book_has_sub_categories');
    }
};
