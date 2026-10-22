<?php

namespace Modules\Authorization\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authorization\Models\Menu;
use Modules\Authorization\Models\MenuItem;
use Modules\Authorization\Models\MenuItemRole;
use Modules\Authorization\Models\MenuRole;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\SystemModule;

/**
 * Assigns each menu item to a workspace module and seeds Circulation reports.
 */
class MenuWorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        $modules = SystemModule::query()->pluck('id', 'code');

        $itemModuleMap = [
            'dashboard-home' => 'access-management',
            'system-roles' => 'access-management',
            'system-users' => 'access-management',
            'role-assignment' => 'access-management',
            'active-sessions' => 'access-management',
            'system-menu' => 'access-management',
            'system-actions' => 'access-management',
            'system-workspaces' => 'access-management',
            'readings-history' => 'access-management',
            'reports' => 'access-management',
            'audit-log' => 'access-management',
            'appearance' => 'system-settings',
            'login-slider' => 'system-settings',
            'borrowing-processes' => 'system-settings',
            'penalty-policies' => 'system-settings',
            'librarian-tags' => 'manage-catalog',
            'librarian-categories' => 'manage-catalog',
            'librarian-sub-categories' => 'manage-catalog',
            'librarian-shelves' => 'manage-catalog',
            'librarian-books' => 'manage-catalog',
            'librarian-dashboard-home' => 'circulation',
            'librarian-borrowing-requests' => 'circulation',
            'librarian-penalties' => 'circulation',
            'librarian-readings-history' => 'circulation',
            'librarian-reports' => 'circulation',
        ];

        foreach ($itemModuleMap as $itemCode => $moduleCode) {
            $moduleId = $modules[$moduleCode] ?? null;
            if (! $moduleId) {
                continue;
            }

            MenuItem::query()
                ->where('code', $itemCode)
                ->update(['system_module_id' => $moduleId]);
        }

        $this->seedLibrarianReports($modules['circulation'] ?? null);
    }

    protected function seedLibrarianReports(?int $circulationModuleId): void
    {
        if (! $circulationModuleId) {
            return;
        }

        $librarian = Role::query()->where('code', 'librarian')->first();
        if (! $librarian) {
            return;
        }

        $menu = Menu::query()->updateOrCreate(
            ['code' => 'librarian-reports'],
            [
                'name' => 'Reports',
                'icon_code' => 'report',
                'sort_order' => 50,
                'is_active' => true,
                'is_public' => false,
            ],
        );

        MenuRole::query()->firstOrCreate([
            'menu_id' => $menu->id,
            'role_id' => $librarian->id,
        ]);

        $item = MenuItem::query()->updateOrCreate(
            ['code' => 'librarian-reports'],
            [
                'menu_id' => $menu->id,
                'name' => 'Reports',
                'route' => '/librarian/reports',
                'sort_order' => 10,
                'is_active' => true,
                'is_public' => false,
                'system_module_id' => $circulationModuleId,
            ],
        );

        MenuItemRole::query()->firstOrCreate([
            'menu_item_id' => $item->id,
            'role_id' => $librarian->id,
        ]);
    }
}
