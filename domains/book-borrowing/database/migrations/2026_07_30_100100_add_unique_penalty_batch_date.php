<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('book_borrowing.borrowing_penalties', function (Blueprint $table) {
            $table->unique(
                'penalty_date',
                'uq_brrw_pen_batch_date',
            );
        });
    }

    public function down(): void
    {
        Schema::table('book_borrowing.borrowing_penalties', function (Blueprint $table) {
            $table->dropUnique('uq_brrw_pen_batch_date');
        });
    }
};
