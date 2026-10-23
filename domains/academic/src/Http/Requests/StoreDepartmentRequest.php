<?php

namespace Modules\Academic\Http\Requests;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'faculty_id' => ['required', 'integer', 'exists:'.SchemaTable::forValidation('academic.faculties').',id'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique(SchemaTable::forValidation('academic.departments'), 'code')],
            'name_en' => ['required', 'string', 'max:255'],
            'name_sw' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
