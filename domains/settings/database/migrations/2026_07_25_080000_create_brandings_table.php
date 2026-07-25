<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS settings');

        Schema::create('settings.brandings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name');
            $table->string('primary_color', 7);
            $table->string('secondary_color', 7);
            $table->string('tertiary_color', 7);
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings.brandings');
    }
};
