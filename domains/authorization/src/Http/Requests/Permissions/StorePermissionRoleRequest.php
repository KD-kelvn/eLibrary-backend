<?php

namespace Modules\Authorization\Http\Requests\Permissions;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasActiveRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'system_action_id' => ['required', 'integer', 'exists:'.SchemaTable::forValidation('auth.system_actions').',id'],
            'role_id' => ['required', 'integer', 'exists:'.SchemaTable::forValidation('auth.roles').',id'],
        ];
    }
}
