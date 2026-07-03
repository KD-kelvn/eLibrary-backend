<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('auth.system_module_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('system_module_id');
            $table->unsignedBigInteger('role_id');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('system_module_id')->references('id')->on('auth.system_modules')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('auth.roles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth.system_module_roles');
    }
};
