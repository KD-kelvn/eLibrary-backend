<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS auth');

        DB::statement('ALTER TABLE IF EXISTS public.users SET SCHEMA auth');
        DB::statement('ALTER TABLE IF EXISTS public.user_profiles SET SCHEMA auth');
        DB::statement('ALTER TABLE IF EXISTS public.one_time_tokens SET SCHEMA auth');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE IF EXISTS auth.one_time_tokens SET SCHEMA public');
        DB::statement('ALTER TABLE IF EXISTS auth.user_profiles SET SCHEMA public');
        DB::statement('ALTER TABLE IF EXISTS auth.users SET SCHEMA public');
    }
};
