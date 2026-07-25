<?php

namespace Modules\Authorization\Database\Seeders;

use Illuminate\Database\Seeder;

class AuthorizationDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminPermissionSeeder::class,
        ]);
    }
}
