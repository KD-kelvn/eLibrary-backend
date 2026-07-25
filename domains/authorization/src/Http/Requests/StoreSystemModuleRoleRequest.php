<?php

namespace Modules\Authorization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Authorization\Models\SystemModuleRole;

class StoreSystemModuleRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SystemModuleRole::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'system_module_id' => ['required', 'integer', 'exists:auth.system_modules,id'],
            'role_id' => ['required', 'integer', 'exists:auth.roles,id'],
        ];
    }
}
