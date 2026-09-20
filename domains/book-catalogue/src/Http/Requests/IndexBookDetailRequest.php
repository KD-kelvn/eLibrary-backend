<?php

namespace Modules\BookCatalogue\Http\Requests;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\BookCatalogue\Enums\BookTypeEnum;

class IndexBookDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'type_code' => ['nullable', Rule::enum(BookTypeEnum::class)],
            'category_id' => [
                'nullable',
                'integer',
                'exists:'.SchemaTable::forValidation('book_catalog.categories').',id',
            ],
            'sub_category_id' => [
                'nullable',
                'integer',
                'exists:'.SchemaTable::forValidation('book_catalog.sub_categories').',id',
            ],
            'tag_id' => [
                'nullable',
                'integer',
                'exists:'.SchemaTable::forValidation('book_catalog.tags').',id',
            ],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
