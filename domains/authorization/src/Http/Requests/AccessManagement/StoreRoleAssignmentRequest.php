<?php

namespace Modules\Authorization\Http\Requests\AccessManagement;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Authorization\Models\UserRole;

class StoreRoleAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', UserRole::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:'.SchemaTable::forValidation('auth.users').',id'],
            'role_id' => ['required', 'integer', 'exists:'.SchemaTable::forValidation('auth.roles').',id'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
