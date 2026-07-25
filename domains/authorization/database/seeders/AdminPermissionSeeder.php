<?php

namespace Modules\Authorization\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authorization\Models\Menu;
use Modules\Authorization\Models\MenuItem;
use Modules\Authorization\Models\MenuItemRole;
use Modules\Authorization\Models\MenuRole;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\SystemActionRole;

class AdminPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::query()->where('code', 'admin')->firstOrFail();

        $definitions = [
            [
                'name' => 'Dashboard',
                'code' => 'dashboard',
                'icon_code' => 'dashboard',
                'sort_order' => 10,
                'items' => [
                    ['name' => 'Dashboard', 'code' => 'dashboard-home', 'route' => '/admin', 'sort_order' => 10],
                ],
            ],
            [
                'name' => 'Account access',
                'code' => 'account-access',
                'icon_code' => 'lock-access',
                'sort_order' => 20,
                'items' => [
                    ['name' => 'System roles', 'code' => 'system-roles', 'route' => '/admin/access/roles', 'sort_order' => 10],
                    ['name' => 'System users', 'code' => 'system-users', 'route' => '/admin/access/users', 'sort_order' => 20],
                    ['name' => 'Role assignment', 'code' => 'role-assignment', 'route' => '/admin/access/role-assignments', 'sort_order' => 30],
                ],
            ],
            [
                'name' => 'Permissions',
                'code' => 'permissions',
                'icon_code' => 'permissions',
                'sort_order' => 30,
                'items' => [
                    ['name' => 'System menu', 'code' => 'system-menu', 'route' => '/admin/permissions/menus', 'sort_order' => 10],
                    ['name' => 'System actions', 'code' => 'system-actions', 'route' => '/admin/permissions/actions', 'sort_order' => 20],
                ],
            ],
            [
                'name' => 'Readings',
                'code' => 'readings',
                'icon_code' => 'books',
                'sort_order' => 40,
                'items' => [
                    ['name' => 'Readings', 'code' => 'readings-history', 'route' => '/admin/readings', 'sort_order' => 10],
                ],
            ],
            [
                'name' => 'Report',
                'code' => 'report',
                'icon_code' => 'report',
                'sort_order' => 50,
                'items' => [
                    ['name' => 'Report', 'code' => 'reports', 'route' => '/admin/reports', 'sort_order' => 10],
                ],
            ],
            [
                'name' => 'Settings',
                'code' => 'settings',
                'icon_code' => 'settings',
                'sort_order' => 60,
                'items' => [
                    ['name' => 'Appearance', 'code' => 'appearance', 'route' => '/admin/settings/appearance', 'sort_order' => 10],
                ],
            ],
        ];

        $items = [];

        foreach ($definitions as $definition) {
            $itemDefinitions = $definition['items'];
            unset($definition['items']);

            $menu = Menu::query()->updateOrCreate(
                ['code' => $definition['code']],
                [...$definition, 'is_active' => true, 'is_public' => false],
            );

            MenuRole::query()->firstOrCreate(['menu_id' => $menu->id, 'role_id' => $admin->id]);

            foreach ($itemDefinitions as $itemDefinition) {
                $item = MenuItem::query()->updateOrCreate(
                    ['code' => $itemDefinition['code']],
                    [
                        ...$itemDefinition,
                        'menu_id' => $menu->id,
                        'is_active' => true,
                        'is_public' => false,
                    ],
                );
                MenuItemRole::query()->firstOrCreate([
                    'menu_item_id' => $item->id,
                    'role_id' => $admin->id,
                ]);
                $items[$item->code] = $item;
            }
        }

        foreach ([
            ['name' => 'Create roles', 'code' => 'roles.create', 'item' => 'system-roles', 'type' => 'create'],
            ['name' => 'Update roles', 'code' => 'roles.update', 'item' => 'system-roles', 'type' => 'update'],
            ['name' => 'Delete roles', 'code' => 'roles.delete', 'item' => 'system-roles', 'type' => 'delete'],
            ['name' => 'Create users', 'code' => 'users.create', 'item' => 'system-users', 'type' => 'create'],
            ['name' => 'Update users', 'code' => 'users.update', 'item' => 'system-users', 'type' => 'update'],
            ['name' => 'Block users', 'code' => 'users.block', 'item' => 'system-users', 'type' => 'update'],
            ['name' => 'Delete users', 'code' => 'users.delete', 'item' => 'system-users', 'type' => 'delete'],
            ['name' => 'Assign roles', 'code' => 'role-assignments.create', 'item' => 'role-assignment', 'type' => 'create'],
            ['name' => 'Update role assignments', 'code' => 'role-assignments.update', 'item' => 'role-assignment', 'type' => 'update'],
            ['name' => 'Revoke role assignments', 'code' => 'role-assignments.revoke', 'item' => 'role-assignment', 'type' => 'delete'],
            ['name' => 'Create menus', 'code' => 'menus.create', 'item' => 'system-menu', 'type' => 'create'],
            ['name' => 'Update menus', 'code' => 'menus.update', 'item' => 'system-menu', 'type' => 'update'],
            ['name' => 'Delete menus', 'code' => 'menus.delete', 'item' => 'system-menu', 'type' => 'delete'],
            ['name' => 'Create actions', 'code' => 'actions.create', 'item' => 'system-actions', 'type' => 'create'],
            ['name' => 'Update actions', 'code' => 'actions.update', 'item' => 'system-actions', 'type' => 'update'],
            ['name' => 'Delete actions', 'code' => 'actions.delete', 'item' => 'system-actions', 'type' => 'delete'],
            ['name' => 'Export readings', 'code' => 'readings.export', 'item' => 'readings-history', 'type' => 'export'],
            ['name' => 'Update appearance', 'code' => 'appearance.update', 'item' => 'appearance', 'type' => 'update'],
        ] as $definition) {
            $action = SystemAction::query()->updateOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'menu_item_id' => $items[$definition['item']]->id,
                    'action_type' => $definition['type'],
                    'is_active' => true,
                ],
            );
            SystemActionRole::query()->firstOrCreate([
                'system_action_id' => $action->id,
                'role_id' => $admin->id,
            ]);
        }
    }
}
