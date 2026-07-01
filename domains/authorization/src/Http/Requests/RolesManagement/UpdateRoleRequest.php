<?php

namespace Modules\Authorization\Http\Requests\RolesManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Authorization\Enums\RoleStatusEnum;
use Modules\Authorization\Models\Role;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = Role::query()->find($this->route('role'));

        return $role && $this->user()?->can('update', $role);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $roleId = $this->route('role');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', Rule::enum(RoleStatusEnum::class)],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('roles', 'code')->ignore($roleId),
            ],
        ];
    }
}
