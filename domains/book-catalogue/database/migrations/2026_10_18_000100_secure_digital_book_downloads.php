<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('book_catalog.digital_books')->update(['is_downloadable' => false]);

        Schema::table('book_catalog.digital_books', function (Blueprint $table) {
            $table->boolean('is_downloadable')->default(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('book_catalog.digital_books', function (Blueprint $table) {
            $table->boolean('is_downloadable')->default(true)->change();
        });
    }
};
