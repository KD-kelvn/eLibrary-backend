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
        $faculties = SchemaTable::forValidation('academic.faculties');
        $departments = SchemaTable::forValidation('academic.departments');
        $programmes = SchemaTable::forValidation('academic.programmes');

        return [
            'username' => ['sometimes', 'required', 'string', 'max:100', Rule::unique(SchemaTable::forValidation('auth.users'), 'username')->ignore($id)],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique(SchemaTable::forValidation('auth.users'), 'email')->ignore($id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30', Rule::unique(SchemaTable::forValidation('auth.users'), 'phone')->ignore($id)],
            'password' => ['sometimes', 'required', 'confirmed', Password::defaults()],
            'fullname' => ['sometimes', 'required', 'string', 'max:255'],
            'gender' => ['sometimes', 'nullable', 'string', 'max:50'],
            'dob' => ['sometimes', 'nullable', 'date', 'before:today'],
            'is_blocked' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'locale' => ['sometimes', 'string', 'max:10', 'in:en,sw'],
            'break_glass' => ['sometimes', 'boolean'],
            'deactivated_reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'faculty_id' => ['sometimes', 'nullable', 'integer', 'exists:'.$faculties.',id'],
            'department_id' => ['sometimes', 'nullable', 'integer', 'exists:'.$departments.',id'],
            'programme_id' => ['sometimes', 'nullable', 'integer', 'exists:'.$programmes.',id'],
            'year_of_study' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10'],
        ];
    }
}
