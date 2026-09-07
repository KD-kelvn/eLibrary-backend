<?php

namespace Modules\Authentication\Http\Requests;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Authentication\Models\User;

class UpdateManagedUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = User::query()->find($this->route('user'));

        return $user && ($this->user()?->can('update', $user) ?? false);
    }

    public function rules(): array
    {
        $id = $this->route('user');

        return [
            'username' => ['sometimes', 'required', 'string', 'max:100', Rule::unique(SchemaTable::forValidation('auth.users'), 'username')->ignore($id)],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique(SchemaTable::forValidation('auth.users'), 'email')->ignore($id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30', Rule::unique(SchemaTable::forValidation('auth.users'), 'phone')->ignore($id)],
            'password' => ['sometimes', 'required', 'confirmed', Password::defaults()],
            'fullname' => ['sometimes', 'required', 'string', 'max:255'],
            'gender' => ['sometimes', 'nullable', 'string', 'max:50'],
            'dob' => ['sometimes', 'nullable', 'date', 'before:today'],
            'is_blocked' => ['sometimes', 'boolean'],
        ];
    }
}
