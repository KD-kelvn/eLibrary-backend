<?php

namespace Modules\Authorization\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\SystemModule;
use Modules\Authorization\Models\SystemModuleRole;

class SystemModuleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::query()->where('code', 'admin')->firstOrFail();
        $librarian = Role::query()->where('code', 'librarian')->firstOrFail();

        $definitions = [
            [
                'name' => 'Access Management',
                'description' => 'Manage sessions, roles, accounts, menus, permissions, and the admin dashboard.',
                'code' => 'access-management',
                'icon_code' => 'lock-access',
                'bg_color' => '#0F4C5C',
                'landing_url' => '/admin',
                'sort_order' => 10,
                'is_public' => false,
                'is_active' => true,
                'badge' => 'ADMIN',
                'roles' => [$admin->id],
            ],
            [
                'name' => 'System Settings',
                'description' => 'Configure appearance, the login slider, borrowing processes, and penalty policies.',
                'code' => 'system-settings',
                'icon_code' => 'settings',
                'bg_color' => '#2D6A4F',
                'landing_url' => '/admin/settings/appearance',
                'sort_order' => 20,
                'is_public' => false,
                'is_active' => true,
                'badge' => 'ADMIN',
                'roles' => [$admin->id],
            ],
            [
                'name' => 'Read & Borrow',
                'description' => 'Explore books, track readings and borrowings, view penalties, and open your dashboard.',
                'code' => 'read-borrow',
                'icon_code' => 'books',
                'bg_color' => '#0D9488',
                'landing_url' => '/',
                'sort_order' => 30,
                'is_public' => true,
                'is_active' => true,
                'badge' => 'MEMBER',
                'roles' => [],
            ],
            [
                'name' => 'Manage Catalog',
                'description' => 'Organize tags, categories, sub-categories, shelves, and book titles.',
                'code' => 'manage-catalog',
                'icon_code' => 'warehouse',
                'bg_color' => '#1D3557',
                'landing_url' => '/librarian/catalogue/books',
                'sort_order' => 40,
                'is_public' => false,
                'is_active' => true,
                'badge' => 'LIBRARIAN',
                'roles' => [$librarian->id],
            ],
            [
                'name' => 'Circulation',
                'description' => 'Handle borrowing requests, raised penalties, student readings, dashboard, and reports.',
                'code' => 'circulation',
                'icon_code' => 'clipboard-list',
                'bg_color' => '#9B2226',
                'landing_url' => '/librarian',
                'sort_order' => 50,
                'is_public' => false,
                'is_active' => true,
                'badge' => 'LIBRARIAN',
                'roles' => [$librarian->id],
            ],
        ];

        foreach ($definitions as $definition) {
            $roleIds = $definition['roles'];
            unset($definition['roles']);

            $module = SystemModule::query()->updateOrCreate(
                ['code' => $definition['code']],
                $definition,
            );

            foreach ($roleIds as $roleId) {
                SystemModuleRole::query()->firstOrCreate([
                    'system_module_id' => $module->id,
                    'role_id' => $roleId,
                ]);
            }
        }
    }
}
