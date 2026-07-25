<?php

namespace Modules\Authorization\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authorization\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Administrator', 'code' => 'admin', 'description' => 'Full system administration access'],
            ['name' => 'Librarian', 'code' => 'librarian', 'description' => 'Catalogue and circulation management'],
            ['name' => 'View only', 'code' => 'view_only', 'description' => 'Read-only audit and management access'],
            ['name' => 'Student', 'code' => 'student', 'description' => 'Student library access'],
        ] as $role) {
            Role::query()->updateOrCreate(
                ['code' => $role['code']],
                [...$role, 'status' => 'active'],
            );
        }
    }
}
