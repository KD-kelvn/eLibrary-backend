<?php

namespace Modules\BookBorrowing\Http\Requests;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;

class StoreBorrowingRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'book_id' => [
                'required',
                'integer',
                'exists:'.SchemaTable::forValidation('book_catalog.book_details').',id',
            ],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
