<?php

namespace Modules\Authorization\Http\Requests\AccessManagement;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Authorization\Models\UserRole;

class IndexRoleAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', UserRole::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['all', 'active', 'expired'])],
            'user_id' => ['nullable', 'integer', 'exists:'.SchemaTable::forValidation('auth.users').',id'],
            'role_id' => ['nullable', 'integer', 'exists:'.SchemaTable::forValidation('auth.roles').',id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
