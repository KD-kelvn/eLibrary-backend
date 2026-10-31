<?php

namespace Modules\Integrations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PushUniversityStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'students' => ['required', 'array', 'min:1', 'max:500'],
            'students.*.reg_no' => ['required', 'string', 'max:100'],
            'students.*.first_name' => ['required', 'string', 'max:100'],
            'students.*.middle_name' => ['nullable', 'string', 'max:100'],
            'students.*.last_name' => ['required', 'string', 'max:100'],
            'students.*.faculty_code' => ['nullable', 'string', 'max:50'],
            'students.*.department_code' => ['nullable', 'string', 'max:50'],
            'students.*.programme_code' => ['nullable', 'string', 'max:50'],
            'students.*.year_of_study' => ['nullable', 'integer', 'min:1', 'max:10'],
            'students.*.email' => ['required', 'email', 'max:255'],
            'students.*.phone' => ['nullable', 'string', 'max:30'],
            'students.*.status' => ['required', 'string', 'in:active,graduated,withdrawn,suspended'],
            'students.*.updated_at' => ['nullable', 'date'],
        ];
    }
}
