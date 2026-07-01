<?php

namespace Modules\Authorization\Http\Requests\AccessManagement;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Authorization\Models\RevokedRole;

class IndexRoleRevokingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:auth.users,id'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
