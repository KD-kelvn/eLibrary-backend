<?php

namespace Modules\Authentication\Http\Requests;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Authentication\Models\User;

class StoreManagedUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    public function rules(): array
    {
        $faculties = SchemaTable::forValidation('academic.faculties');
        $departments = SchemaTable::forValidation('academic.departments');
        $programmes = SchemaTable::forValidation('academic.programmes');

        return [
            'username' => ['required', 'string', 'max:100', Rule::unique(SchemaTable::forValidation('auth.users'), 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique(SchemaTable::forValidation('auth.users'), 'email')],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique(SchemaTable::forValidation('auth.users'), 'phone')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'fullname' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:50'],
            'dob' => ['nullable', 'date', 'before:today'],
            'role_id' => ['required', 'integer', 'exists:'.SchemaTable::forValidation('auth.roles').',id'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
            'is_blocked' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'locale' => ['nullable', 'string', 'max:10', 'in:en,sw'],
            'break_glass' => ['nullable', 'boolean'],
            'faculty_id' => ['nullable', 'integer', 'exists:'.$faculties.',id'],
            'department_id' => ['nullable', 'integer', 'exists:'.$departments.',id'],
            'programme_id' => ['nullable', 'integer', 'exists:'.$programmes.',id'],
            'year_of_study' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }
}
