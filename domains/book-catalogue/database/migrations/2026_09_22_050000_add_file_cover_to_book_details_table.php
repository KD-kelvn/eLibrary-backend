<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('book_catalog.book_details', function (Blueprint $table) {
            $table->string('file_cover')->nullable()->after('pages');
        });

        // Preserve covers previously stored only on digital books.
        DB::statement('
            UPDATE book_catalog.book_details AS bd
            SET file_cover = db.file_cover
            FROM book_catalog.digital_books AS db
            WHERE db.book_detail_id = bd.id
              AND db.file_cover IS NOT NULL
              AND bd.file_cover IS NULL
              AND db.deleted_at IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('book_catalog.book_details', function (Blueprint $table) {
            $table->dropColumn('file_cover');
        });
    }
};
