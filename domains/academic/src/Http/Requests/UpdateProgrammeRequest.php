<?php

namespace Modules\Academic\Http\Requests;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgrammeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $programmeId = $this->route('programme');

        return [
            'department_id' => ['sometimes', 'required', 'integer', 'exists:'.SchemaTable::forValidation('academic.departments').',id'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique(SchemaTable::forValidation('academic.programmes'), 'code')->ignore($programmeId),
            ],
            'name_en' => ['sometimes', 'required', 'string', 'max:255'],
            'name_sw' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
