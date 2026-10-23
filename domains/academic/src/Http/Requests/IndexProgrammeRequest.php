<?php

namespace Modules\Academic\Http\Requests;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;

class IndexProgrammeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'integer', 'exists:'.SchemaTable::forValidation('academic.departments').',id'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
