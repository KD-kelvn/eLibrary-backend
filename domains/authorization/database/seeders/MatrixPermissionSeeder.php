<?php

namespace Modules\Authorization\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\SystemActionRole;

/**
 * Seeds Library policy / roles matrix permission codes (design/docs/v1/03-roles-permissions.md).
 * One system_actions row per code; roles linked via system_action_roles (+ owning role_id).
 */
class MatrixPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::query()
            ->whereIn('code', ['student', 'faculty', 'librarian', 'admin'])
            ->get()
            ->keyBy('code');

        foreach ($this->matrix() as $code => $roleCodes) {
            $ownerCode = $this->ownerRole($roleCodes);
            $owner = $roles->get($ownerCode);

            if (! $owner) {
                continue;
            }

            $action = SystemAction::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $this->label($code),
                    'description' => 'Matrix permission (V1-003)',
                    'role_id' => $owner->id,
                    'action_type' => 'permission',
                    'is_active' => true,
                    'menu_item_id' => null,
                ],
            );

            foreach ($roleCodes as $roleCode) {
                $role = $roles->get($roleCode);
                if (! $role) {
                    continue;
                }

                SystemActionRole::query()->firstOrCreate([
                    'system_action_id' => $action->id,
                    'role_id' => $role->id,
                ]);
            }
        }
    }

    /**
     * @return array<string, list<string>>
     */
    protected function matrix(): array
    {
        return [
            'catalogue.view' => ['student', 'faculty', 'librarian', 'admin'],
            'catalogue.manage' => ['librarian'],
            'copies.manage' => ['librarian'],
            'copies.print_labels' => ['librarian'],
            'reading.read' => ['student', 'faculty', 'librarian', 'admin'],
            'reading.history.view' => ['student', 'faculty'],
            'favourites.manage' => ['student', 'faculty', 'librarian'],
            'requests.create' => ['student', 'faculty'],
            'requests.cancel' => ['student', 'faculty'],
            'requests.decide' => ['librarian'],
            'loans.view' => ['student', 'faculty', 'librarian'],
            'loans.issue' => ['librarian'],
            'loans.return' => ['librarian'],
            'loans.renew' => ['student', 'faculty', 'librarian'],
            'loans.renew_override' => ['librarian'],
            'loans.declare_lost_damaged' => ['librarian'],
            'holds.create' => ['student', 'faculty', 'librarian'],
            'holds.cancel' => ['student', 'faculty', 'librarian'],
            'penalties.view' => ['student', 'faculty', 'librarian'],
            'penalties.mark_paid' => ['librarian'],
            'penalties.forgive' => ['librarian'],
            'penalties.revert' => ['librarian'],
            'recommendations.create' => ['faculty'],
            'recommendations.view' => ['faculty', 'librarian'],
            'recommendations.decide' => ['librarian'],
            'insights.faculty.view' => ['faculty'],
            'reports.view' => ['librarian', 'admin'],
            'reports.export' => ['librarian', 'admin'],
            'notifications.preferences' => ['student', 'faculty', 'librarian', 'admin'],
            'users.manage' => ['admin'],
            'users.import' => ['admin'],
            'academic.manage' => ['admin'],
            'sync.view' => ['admin'],
            'sync.run' => ['admin'],
            'roles.manage' => ['admin'],
            'permissions.assign' => ['admin'],
            'sessions.view_own' => ['student', 'faculty', 'librarian', 'admin'],
            'sessions.revoke_own' => ['student', 'faculty', 'librarian', 'admin'],
            'sessions.manage' => ['admin'],
            'audit.view' => ['admin'],
            'audit.export' => ['admin'],
            'health.view' => ['admin'],
            'settings.library.view' => ['librarian', 'admin'],
            'settings.library.update' => ['admin'],
            'settings.library.reset' => ['admin'],
            'settings.providers' => ['admin'],
            'branding.manage' => ['admin'],
        ];
    }

    /**
     * Prefer the lowest-privilege role that holds the permission as the owner row.
     *
     * @param  list<string>  $roleCodes
     */
    protected function ownerRole(array $roleCodes): string
    {
        foreach (['student', 'faculty', 'librarian', 'admin'] as $code) {
            if (in_array($code, $roleCodes, true)) {
                return $code;
            }
        }

        return $roleCodes[0];
    }

    protected function label(string $code): string
    {
        return str($code)->replace('.', ' ')->headline()->toString();
    }
}
