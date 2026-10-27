<?php

namespace Modules\Academic\Http\Requests;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $departmentId = $this->route('department');

        return [
            'faculty_id' => ['sometimes', 'required', 'integer', 'exists:'.SchemaTable::forValidation('academic.faculties').',id'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique(SchemaTable::forValidation('academic.departments'), 'code')->ignore($departmentId),
            ],
            'name_en' => ['sometimes', 'required', 'string', 'max:255'],
            'name_sw' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
