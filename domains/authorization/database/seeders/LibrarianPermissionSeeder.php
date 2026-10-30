<?php

namespace Modules\Authorization\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\SystemActionRole;

class LibrarianPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $librarian = Role::query()->where('code', 'librarian')->firstOrFail();

        foreach ([
            ['name' => 'Create tags', 'code' => 'librarian.tags.create', 'type' => 'create'],
            ['name' => 'Update tags', 'code' => 'librarian.tags.update', 'type' => 'update'],
            ['name' => 'Delete tags', 'code' => 'librarian.tags.delete', 'type' => 'delete'],
            ['name' => 'Create categories', 'code' => 'librarian.categories.create', 'type' => 'create'],
            ['name' => 'Update categories', 'code' => 'librarian.categories.update', 'type' => 'update'],
            ['name' => 'Delete categories', 'code' => 'librarian.categories.delete', 'type' => 'delete'],
            ['name' => 'Create sub-categories', 'code' => 'librarian.sub-categories.create', 'type' => 'create'],
            ['name' => 'Update sub-categories', 'code' => 'librarian.sub-categories.update', 'type' => 'update'],
            ['name' => 'Delete sub-categories', 'code' => 'librarian.sub-categories.delete', 'type' => 'delete'],
            ['name' => 'Create shelves', 'code' => 'librarian.shelves.create', 'type' => 'create'],
            ['name' => 'Update shelves', 'code' => 'librarian.shelves.update', 'type' => 'update'],
            ['name' => 'Delete shelves', 'code' => 'librarian.shelves.delete', 'type' => 'delete'],
            ['name' => 'Create books', 'code' => 'librarian.books.create', 'type' => 'create'],
            ['name' => 'Update books', 'code' => 'librarian.books.update', 'type' => 'update'],
            ['name' => 'Delete books', 'code' => 'librarian.books.delete', 'type' => 'delete'],
            ['name' => 'Approve borrowing', 'code' => 'librarian.requests.approve', 'type' => 'update'],
            ['name' => 'Reject borrowing', 'code' => 'librarian.requests.reject', 'type' => 'update'],
            ['name' => 'Mark returned', 'code' => 'librarian.requests.mark-returned', 'type' => 'update'],
            ['name' => 'Delete borrowing request', 'code' => 'librarian.requests.delete', 'type' => 'delete'],
            ['name' => 'Stop penalty batch', 'code' => 'librarian.penalties.stop', 'type' => 'update'],
            ['name' => 'Mark penalty paid', 'code' => 'librarian.penalties.mark-paid', 'type' => 'update'],
            ['name' => 'Export readings', 'code' => 'librarian.readings.export', 'type' => 'export'],
        ] as $definition) {
            $action = SystemAction::query()->updateOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'role_id' => $librarian->id,
                    'action_type' => $definition['type'],
                    'is_active' => true,
                ],
            );
            SystemActionRole::query()->firstOrCreate([
                'system_action_id' => $action->id,
                'role_id' => $librarian->id,
            ]);
        }
    }
}
