<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS auth');

        Schema::create('auth.one_time_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('token');
            $table->string('via')->comment('requested either from email or mobile phone');
            $table->string('expires_at');
            $table->string('purpose')->comment('To be used for unlocking , resetting or one password to login');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('used_at')->nullable();
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth.one_time_tokens');
    }
};
