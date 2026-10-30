<?php

namespace Modules\Authorization\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\SystemActionRole;

class AdminPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::query()->where('code', 'admin')->firstOrFail();

        foreach ([
            ['name' => 'Create roles', 'code' => 'roles.create', 'type' => 'create'],
            ['name' => 'Update roles', 'code' => 'roles.update', 'type' => 'update'],
            ['name' => 'Delete roles', 'code' => 'roles.delete', 'type' => 'delete'],
            ['name' => 'Create users', 'code' => 'users.create', 'type' => 'create'],
            ['name' => 'Update users', 'code' => 'users.update', 'type' => 'update'],
            ['name' => 'Block users', 'code' => 'users.block', 'type' => 'update'],
            ['name' => 'Delete users', 'code' => 'users.delete', 'type' => 'delete'],
            ['name' => 'Assign roles', 'code' => 'role-assignments.create', 'type' => 'create'],
            ['name' => 'Update role assignments', 'code' => 'role-assignments.update', 'type' => 'update'],
            ['name' => 'Revoke role assignments', 'code' => 'role-assignments.revoke', 'type' => 'delete'],
            ['name' => 'View sessions', 'code' => 'sessions.view', 'type' => 'view'],
            ['name' => 'Revoke sessions', 'code' => 'sessions.revoke', 'type' => 'delete'],
            ['name' => 'Create actions', 'code' => 'actions.create', 'type' => 'create'],
            ['name' => 'Update actions', 'code' => 'actions.update', 'type' => 'update'],
            ['name' => 'Delete actions', 'code' => 'actions.delete', 'type' => 'delete'],
            ['name' => 'Export readings', 'code' => 'readings.export', 'type' => 'export'],
            ['name' => 'Update appearance', 'code' => 'appearance.update', 'type' => 'update'],
            ['name' => 'Create login slides', 'code' => 'login-slides.create', 'type' => 'create'],
            ['name' => 'Update login slides', 'code' => 'login-slides.update', 'type' => 'update'],
            ['name' => 'Delete login slides', 'code' => 'login-slides.delete', 'type' => 'delete'],
            ['name' => 'Create borrowing processes', 'code' => 'borrowing-processes.create', 'type' => 'create'],
            ['name' => 'Update borrowing processes', 'code' => 'borrowing-processes.update', 'type' => 'update'],
            ['name' => 'Delete borrowing processes', 'code' => 'borrowing-processes.delete', 'type' => 'delete'],
            ['name' => 'Create penalty policies', 'code' => 'penalty-policies.create', 'type' => 'create'],
            ['name' => 'Update penalty policies', 'code' => 'penalty-policies.update', 'type' => 'update'],
            ['name' => 'Delete penalty policies', 'code' => 'penalty-policies.delete', 'type' => 'delete'],
        ] as $definition) {
            $action = SystemAction::query()->updateOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'role_id' => $admin->id,
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
