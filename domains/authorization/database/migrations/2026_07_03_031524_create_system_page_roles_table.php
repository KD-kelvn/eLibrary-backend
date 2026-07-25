<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('auth.system_page_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('system_page_id');
            $table->unsignedBigInteger('role_id');
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('system_page_id')->references('id')->on('auth.system_pages')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('auth.roles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth.system_page_roles');
    }
};
