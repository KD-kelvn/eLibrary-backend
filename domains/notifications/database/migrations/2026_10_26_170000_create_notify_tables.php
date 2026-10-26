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
            DB::statement('CREATE SCHEMA IF NOT EXISTS notify');
        }

        Schema::create($this->table('notifications'), function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create($this->table('preferences'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('category', 64);
            $table->string('channel', 32);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'category', 'channel'], 'notify_prefs_user_cat_channel_unique');
            $table->index(['user_id', 'category']);
        });

        Schema::create($this->table('deliveries'), function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('category', 64);
            $table->string('channel', 32);
            $table->string('provider', 64)->nullable();
            $table->string('provider_message_id')->nullable();
            $table->string('status', 32)->default('queued');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'category', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table('deliveries'));
        Schema::dropIfExists($this->table('preferences'));
        Schema::dropIfExists($this->table('notifications'));
    }

    protected function table(string $name): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'notify.'.$name
            : 'notify_'.$name;
    }
};
