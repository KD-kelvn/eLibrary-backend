<?php

namespace Modules\Authorization\Http\Requests\AccessManagement;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Authorization\Models\RevokedRole;

class StoreRoleRevokingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', RevokedRole::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_role_id' => ['required', 'integer', 'exists:auth.user_roles,id'],
            'reason' => ['nullable', 'string'],
        ];
    }
}
