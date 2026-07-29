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
                    ['name' => 'Sessions', 'code' => 'active-sessions', 'route' => '/admin/access/sessions', 'sort_order' => 40],
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
                    ['name' => 'Login slider', 'code' => 'login-slider', 'route' => '/admin/settings/login-slider', 'sort_order' => 20],
                    ['name' => 'Borrowing processes', 'code' => 'borrowing-processes', 'route' => '/admin/settings/borrowing-processes', 'sort_order' => 30],
                    ['name' => 'Penalty policies', 'code' => 'penalty-policies', 'route' => '/admin/settings/penalty-policies', 'sort_order' => 40],
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
            ['name' => 'View sessions', 'code' => 'sessions.view', 'item' => 'active-sessions', 'type' => 'view'],
            ['name' => 'Revoke sessions', 'code' => 'sessions.revoke', 'item' => 'active-sessions', 'type' => 'delete'],
            ['name' => 'Create menus', 'code' => 'menus.create', 'item' => 'system-menu', 'type' => 'create'],
            ['name' => 'Update menus', 'code' => 'menus.update', 'item' => 'system-menu', 'type' => 'update'],
            ['name' => 'Delete menus', 'code' => 'menus.delete', 'item' => 'system-menu', 'type' => 'delete'],
            ['name' => 'Create actions', 'code' => 'actions.create', 'item' => 'system-actions', 'type' => 'create'],
            ['name' => 'Update actions', 'code' => 'actions.update', 'item' => 'system-actions', 'type' => 'update'],
            ['name' => 'Delete actions', 'code' => 'actions.delete', 'item' => 'system-actions', 'type' => 'delete'],
            ['name' => 'Export readings', 'code' => 'readings.export', 'item' => 'readings-history', 'type' => 'export'],
            ['name' => 'Update appearance', 'code' => 'appearance.update', 'item' => 'appearance', 'type' => 'update'],
            ['name' => 'Create login slides', 'code' => 'login-slides.create', 'item' => 'login-slider', 'type' => 'create'],
            ['name' => 'Update login slides', 'code' => 'login-slides.update', 'item' => 'login-slider', 'type' => 'update'],
            ['name' => 'Delete login slides', 'code' => 'login-slides.delete', 'item' => 'login-slider', 'type' => 'delete'],
            ['name' => 'Create borrowing processes', 'code' => 'borrowing-processes.create', 'item' => 'borrowing-processes', 'type' => 'create'],
            ['name' => 'Update borrowing processes', 'code' => 'borrowing-processes.update', 'item' => 'borrowing-processes', 'type' => 'update'],
            ['name' => 'Delete borrowing processes', 'code' => 'borrowing-processes.delete', 'item' => 'borrowing-processes', 'type' => 'delete'],
            ['name' => 'Create penalty policies', 'code' => 'penalty-policies.create', 'item' => 'penalty-policies', 'type' => 'create'],
            ['name' => 'Update penalty policies', 'code' => 'penalty-policies.update', 'item' => 'penalty-policies', 'type' => 'update'],
            ['name' => 'Delete penalty policies', 'code' => 'penalty-policies.delete', 'item' => 'penalty-policies', 'type' => 'delete'],
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
