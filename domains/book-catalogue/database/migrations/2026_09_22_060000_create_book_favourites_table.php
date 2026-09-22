<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_catalog.book_favourites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('book_detail_id');
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('book_detail_id')
                ->references('id')
                ->on('book_catalog.book_details')
                ->cascadeOnDelete();

            $table->index(['user_id', 'book_detail_id']);
        });

        DB::statement('
            CREATE UNIQUE INDEX book_favourites_user_book_unique
            ON book_catalog.book_favourites (user_id, book_detail_id)
            WHERE deleted_at IS NULL
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('book_catalog.book_favourites');
    }
};
