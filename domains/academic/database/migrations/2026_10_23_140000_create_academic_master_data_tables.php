<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE SCHEMA IF NOT EXISTS academic');
        }

        Schema::create($this->table('faculties'), function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_sw')->nullable();
            $table->boolean('is_active')->default(true);
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($this->table('departments'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('faculty_id');
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_sw')->nullable();
            $table->boolean('is_active')->default(true);
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('faculty_id')
                ->references('id')
                ->on($this->table('faculties'));
        });

        Schema::create($this->table('programmes'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_sw')->nullable();
            $table->boolean('is_active')->default(true);
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('department_id')
                ->references('id')
                ->on($this->table('departments'));
        });

        Schema::create($this->table('courses'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('programme_id');
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_sw')->nullable();
            $table->boolean('is_active')->default(true);
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('programme_id')
                ->references('id')
                ->on($this->table('programmes'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table('courses'));
        Schema::dropIfExists($this->table('programmes'));
        Schema::dropIfExists($this->table('departments'));
        Schema::dropIfExists($this->table('faculties'));
    }

    protected function table(string $name): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? "academic.{$name}"
            : $name;
    }
};
