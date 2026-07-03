<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_catalog.book_details', function (Blueprint $table) {
            $table->id();
            $table->string('type_code');  // PHYSICAL, DIGITAL
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('authors');
            $table->string('isbn');
            $table->string('publisher');
            $table->year('pub_year');

            $table->string('edition')->nullable();
            $table->string('language');
            $table->integer('pages');

            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->index('pub_year');
            $table->index('isbn');
            $table->index('type_code');
            $table->index('authors');
            $table->index('publisher');
            $table->index('language');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_catalog.book_details');
    }
};
