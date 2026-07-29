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

class LibrarianPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $librarian = Role::query()->where('code', 'librarian')->firstOrFail();

        $definitions = [
            [
                'name' => 'Dashboard',
                'code' => 'librarian-dashboard',
                'icon_code' => 'dashboard',
                'sort_order' => 10,
                'items' => [
                    ['name' => 'Dashboard', 'code' => 'librarian-dashboard-home', 'route' => '/librarian', 'sort_order' => 10],
                ],
            ],
            [
                'name' => 'Catalogue',
                'code' => 'librarian-catalogue',
                'icon_code' => 'books',
                'sort_order' => 20,
                'items' => [
                    ['name' => 'Tags', 'code' => 'librarian-tags', 'route' => '/librarian/catalogue/tags', 'sort_order' => 10],
                    ['name' => 'Categories', 'code' => 'librarian-categories', 'route' => '/librarian/catalogue/categories', 'sort_order' => 20],
                    ['name' => 'Sub-categories', 'code' => 'librarian-sub-categories', 'route' => '/librarian/catalogue/sub-categories', 'sort_order' => 30],
                    ['name' => 'Shelves', 'code' => 'librarian-shelves', 'route' => '/librarian/catalogue/shelves', 'sort_order' => 40],
                    ['name' => 'Books', 'code' => 'librarian-books', 'route' => '/librarian/catalogue/books', 'sort_order' => 50],
                ],
            ],
            [
                'name' => 'Borrowing',
                'code' => 'librarian-borrowing',
                'icon_code' => 'clipboard-list',
                'sort_order' => 30,
                'items' => [
                    ['name' => 'Requests', 'code' => 'librarian-borrowing-requests', 'route' => '/librarian/borrowing/requests', 'sort_order' => 10],
                    ['name' => 'Penalties', 'code' => 'librarian-penalties', 'route' => '/librarian/borrowing/penalties', 'sort_order' => 20],
                ],
            ],
            [
                'name' => 'Readings',
                'code' => 'librarian-readings',
                'icon_code' => 'books',
                'sort_order' => 40,
                'items' => [
                    ['name' => 'Reading history', 'code' => 'librarian-readings-history', 'route' => '/librarian/readings', 'sort_order' => 10],
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

            MenuRole::query()->firstOrCreate(['menu_id' => $menu->id, 'role_id' => $librarian->id]);

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
                    'role_id' => $librarian->id,
                ]);
                $items[$item->code] = $item;
            }
        }

        foreach ([
            ['name' => 'Create tags', 'code' => 'librarian.tags.create', 'item' => 'librarian-tags', 'type' => 'create'],
            ['name' => 'Update tags', 'code' => 'librarian.tags.update', 'item' => 'librarian-tags', 'type' => 'update'],
            ['name' => 'Delete tags', 'code' => 'librarian.tags.delete', 'item' => 'librarian-tags', 'type' => 'delete'],
            ['name' => 'Create categories', 'code' => 'librarian.categories.create', 'item' => 'librarian-categories', 'type' => 'create'],
            ['name' => 'Update categories', 'code' => 'librarian.categories.update', 'item' => 'librarian-categories', 'type' => 'update'],
            ['name' => 'Delete categories', 'code' => 'librarian.categories.delete', 'item' => 'librarian-categories', 'type' => 'delete'],
            ['name' => 'Create sub-categories', 'code' => 'librarian.sub-categories.create', 'item' => 'librarian-sub-categories', 'type' => 'create'],
            ['name' => 'Update sub-categories', 'code' => 'librarian.sub-categories.update', 'item' => 'librarian-sub-categories', 'type' => 'update'],
            ['name' => 'Delete sub-categories', 'code' => 'librarian.sub-categories.delete', 'item' => 'librarian-sub-categories', 'type' => 'delete'],
            ['name' => 'Create shelves', 'code' => 'librarian.shelves.create', 'item' => 'librarian-shelves', 'type' => 'create'],
            ['name' => 'Update shelves', 'code' => 'librarian.shelves.update', 'item' => 'librarian-shelves', 'type' => 'update'],
            ['name' => 'Delete shelves', 'code' => 'librarian.shelves.delete', 'item' => 'librarian-shelves', 'type' => 'delete'],
            ['name' => 'Create books', 'code' => 'librarian.books.create', 'item' => 'librarian-books', 'type' => 'create'],
            ['name' => 'Update books', 'code' => 'librarian.books.update', 'item' => 'librarian-books', 'type' => 'update'],
            ['name' => 'Delete books', 'code' => 'librarian.books.delete', 'item' => 'librarian-books', 'type' => 'delete'],
            ['name' => 'Approve borrowing', 'code' => 'librarian.requests.approve', 'item' => 'librarian-borrowing-requests', 'type' => 'update'],
            ['name' => 'Reject borrowing', 'code' => 'librarian.requests.reject', 'item' => 'librarian-borrowing-requests', 'type' => 'update'],
            ['name' => 'Mark returned', 'code' => 'librarian.requests.mark-returned', 'item' => 'librarian-borrowing-requests', 'type' => 'update'],
            ['name' => 'Delete borrowing request', 'code' => 'librarian.requests.delete', 'item' => 'librarian-borrowing-requests', 'type' => 'delete'],
            ['name' => 'Stop penalty batch', 'code' => 'librarian.penalties.stop', 'item' => 'librarian-penalties', 'type' => 'update'],
            ['name' => 'Mark penalty paid', 'code' => 'librarian.penalties.mark-paid', 'item' => 'librarian-penalties', 'type' => 'update'],
            ['name' => 'Export readings', 'code' => 'librarian.readings.export', 'item' => 'librarian-readings-history', 'type' => 'export'],
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
                'role_id' => $librarian->id,
            ]);
        }
    }
}
