<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('auth.system_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_item_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('code')->unique();
            $table->string('action_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('menu_item_id')
                ->references('id')
                ->on('auth.menu_items')
                ->nullOnDelete();
            $table->index(['menu_item_id', 'is_active']);
        });

        Schema::create('auth.system_action_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('system_action_id');
            $table->unsignedBigInteger('role_id');
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('system_action_id')
                ->references('id')
                ->on('auth.system_actions')
                ->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('auth.roles')->cascadeOnDelete();
            $table->index(['system_action_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth.system_action_roles');
        Schema::dropIfExists('auth.system_actions');
    }
};
