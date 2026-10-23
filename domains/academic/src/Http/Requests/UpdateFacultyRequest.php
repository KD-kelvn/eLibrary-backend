<?php

namespace Modules\Academic\Http\Requests;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFacultyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $facultyId = $this->route('faculty');

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique(SchemaTable::forValidation('academic.faculties'), 'code')->ignore($facultyId),
            ],
            'name_en' => ['sometimes', 'required', 'string', 'max:255'],
            'name_sw' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
