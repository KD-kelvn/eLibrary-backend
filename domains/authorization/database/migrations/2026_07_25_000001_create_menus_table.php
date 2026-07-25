<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('auth.menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('code')->unique();
            $table->string('icon_code')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(false);
            $table->boolean('is_active')->default(true);
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth.menus');
    }
};
