<?php

namespace Modules\Authorization\Http\Requests\RolesManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Authorization\Enums\RoleStatusEnum;
use Modules\Authorization\Models\Role;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Role::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::enum(RoleStatusEnum::class)],
            'code' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('auth.roles', 'code'),
            ],
        ];
    }
}
