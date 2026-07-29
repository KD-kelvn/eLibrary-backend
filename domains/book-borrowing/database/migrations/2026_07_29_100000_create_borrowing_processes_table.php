<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS book_borrowing');

        Schema::create('book_borrowing.borrowing_processes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('index_no')->default(0);
            $table->string('status_name');
            $table->string('status_color', 40);
            $table->string('status_code', 40)->unique();
            $table->unsignedBigInteger('sender_role_id')->nullable();
            $table->unsignedBigInteger('receiver_role_id')->nullable();
            $table->boolean('is_final')->default(false);
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('sender_role_id')->references('id')->on('auth.roles')->nullOnDelete();
            $table->foreign('receiver_role_id')->references('id')->on('auth.roles')->nullOnDelete();
            $table->index('index_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_borrowing.borrowing_processes');
    }
};
