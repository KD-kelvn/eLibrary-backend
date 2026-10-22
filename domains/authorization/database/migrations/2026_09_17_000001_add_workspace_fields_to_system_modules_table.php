<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auth.system_modules', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('landing_url');
            $table->boolean('is_public')->default(false)->after('sort_order');
            $table->boolean('is_active')->default(true)->after('is_public');
            $table->string('badge')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('auth.system_modules', function (Blueprint $table) {
            $table->dropColumn(['sort_order', 'is_public', 'is_active', 'badge']);
        });
    }
};
