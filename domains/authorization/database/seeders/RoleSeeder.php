<?php

namespace Modules\Authorization\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authorization\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'name' => 'System Admin',
                'code' => 'admin',
                'description' => 'System administration, settings, and access control',
                'is_system' => true,
            ],
            [
                'name' => 'Librarian',
                'code' => 'librarian',
                'description' => 'Catalogue and circulation management',
                'is_system' => true,
            ],
            [
                'name' => 'Faculty',
                'code' => 'faculty',
                'description' => 'Faculty member library access and insights',
                'is_system' => true,
            ],
            [
                'name' => 'Member',
                'code' => 'student',
                'description' => 'Member (student) library access',
                'is_system' => true,
            ],
            // Legacy non-system role kept for existing admin user-creation flows.
            [
                'name' => 'View only',
                'code' => 'view_only',
                'description' => 'Read-only audit and management access',
                'is_system' => false,
            ],
        ] as $role) {
            Role::query()->updateOrCreate(
                ['code' => $role['code']],
                [...$role, 'status' => 'active'],
            );
        }
    }
}
