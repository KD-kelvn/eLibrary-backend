<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Authorization\Database\Seeders\AuthorizationDatabaseSeeder;
use Modules\Settings\Database\Seeders\SettingsDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AuthorizationDatabaseSeeder::class,
            SettingsDatabaseSeeder::class,
        ]);
    }
}
