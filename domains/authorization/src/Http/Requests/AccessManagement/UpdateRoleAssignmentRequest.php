<?php

namespace Modules\Authorization\Http\Requests\AccessManagement;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Authorization\Models\UserRole;

class UpdateRoleAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assignment = UserRole::query()->find($this->route('role_assignment'));

        return $assignment && $this->user()?->can('update', $assignment);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'required', 'integer', 'exists:auth.users,id'],
            'role_id' => ['sometimes', 'required', 'integer', 'exists:roles,id'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
