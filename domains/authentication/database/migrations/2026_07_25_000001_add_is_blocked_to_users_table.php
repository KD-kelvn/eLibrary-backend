<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('auth.users', function (Blueprint $table) {
            $table->boolean('is_blocked')->default(false)->index();
        });
    }

    public function down(): void
    {
        Schema::table('auth.users', function (Blueprint $table) {
            $table->dropColumn('is_blocked');
        });
    }
};
