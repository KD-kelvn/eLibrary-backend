<?php

namespace Modules\Authorization\Http\Requests\Permissions;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasActiveRole('admin') ?? false;
    }

    public function rules(): array
    {
        $resourceRule = match (true) {
            $this->routeIs('*menu-item-roles*') => ['menu_item_id' => ['required', 'integer', 'exists:auth.menu_items,id']],
            $this->routeIs('*system-action-roles*') => ['system_action_id' => ['required', 'integer', 'exists:auth.system_actions,id']],
            default => ['menu_id' => ['required', 'integer', 'exists:auth.menus,id']],
        };

        return [
            ...$resourceRule,
            'role_id' => ['required', 'integer', 'exists:auth.roles,id'],
        ];
    }
}
