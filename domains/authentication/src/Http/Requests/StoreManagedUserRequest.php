<?php

namespace Modules\Authentication\Http\Requests;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;
use Modules\Authentication\Models\User;
use Modules\Authentication\Rules\E164Phone;
use Modules\Authentication\Support\PhoneNumberNormalizer;
use Modules\Authorization\Models\Role;

class StoreManagedUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $normalized = app(PhoneNumberNormalizer::class)->normalize($this->input('phone'));
            $this->merge(['phone' => $normalized]);
        }
    }

    public function rules(): array
    {
        $faculties = SchemaTable::forValidation('academic.faculties');
        $departments = SchemaTable::forValidation('academic.departments');
        $programmes = SchemaTable::forValidation('academic.programmes');

        return [
            'username' => ['required', 'string', 'max:100', Rule::unique(SchemaTable::forValidation('auth.users'), 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique(SchemaTable::forValidation('auth.users'), 'email')],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                new E164Phone,
                Rule::unique(SchemaTable::forValidation('auth.users'), 'phone'),
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'fullname' => ['sometimes', 'nullable', 'string', 'max:255'],
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $year = $this->input('year_of_study');
            if ($year === null || $year === '') {
                return;
            }

            $role = Role::query()->find($this->input('role_id'));
            if ($role?->code !== 'student') {
                $validator->errors()->add(
                    'year_of_study',
                    'Year of study applies only to Members (student role).',
                );
            }
        });
    }
}
