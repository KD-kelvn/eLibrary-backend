<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('auth.menu_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('role_id');
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('menu_id')->references('id')->on('auth.menus')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('auth.roles')->cascadeOnDelete();
            $table->index(['menu_id', 'role_id']);
        });

        Schema::create('auth.menu_item_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_item_id');
            $table->unsignedBigInteger('role_id');
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('menu_item_id')->references('id')->on('auth.menu_items')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('auth.roles')->cascadeOnDelete();
            $table->index(['menu_item_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth.menu_item_roles');
        Schema::dropIfExists('auth.menu_roles');
    }
};
