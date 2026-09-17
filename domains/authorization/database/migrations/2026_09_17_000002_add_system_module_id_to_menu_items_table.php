<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('auth.menu_items', function (Blueprint $table) {
            $table->unsignedBigInteger('system_module_id')->nullable()->after('menu_id');
            $table->foreign('system_module_id')
                ->references('id')
                ->on('auth.system_modules')
                ->nullOnDelete();
            $table->index(['system_module_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('auth.menu_items', function (Blueprint $table) {
            $table->dropForeign(['system_module_id']);
            $table->dropIndex(['system_module_id', 'is_active', 'sort_order']);
            $table->dropColumn('system_module_id');
        });
    }
};
