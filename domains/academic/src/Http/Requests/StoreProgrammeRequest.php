<?php

namespace Modules\Academic\Http\Requests;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgrammeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:'.SchemaTable::forValidation('academic.departments').',id'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique(SchemaTable::forValidation('academic.programmes'), 'code')],
            'name_en' => ['required', 'string', 'max:255'],
            'name_sw' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
