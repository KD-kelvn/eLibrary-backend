<?php

namespace Modules\BookCatalogue\Http\Requests;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\BookCatalogue\Enums\BookTypeEnum;
use Modules\BookCatalogue\Models\BookDetail;

class StoreBookDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', BookDetail::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $digital = $this->input('digital_book', []);
        if (is_array($digital)) {
            if (array_key_exists('is_downloadable', $digital)) {
                $digital['is_downloadable'] = filter_var(
                    $digital['is_downloadable'],
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE,
                ) ?? false;
            }
            if (array_key_exists('is_active', $digital)) {
                $digital['is_active'] = filter_var(
                    $digital['is_active'],
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE,
                ) ?? true;
            }
            $this->merge(['digital_book' => $digital]);
        }

        if ($this->filled('description') && $this->input('description') === '') {
            $this->merge(['description' => null]);
        }
        if ($this->filled('edition') && $this->input('edition') === '') {
            $this->merge(['edition' => null]);
        }

        foreach (['category_ids', 'sub_category_ids', 'tag_ids'] as $key) {
            if (! $this->exists($key)) {
                continue;
            }
            $value = $this->input($key);
            if ($value === '' || $value === null) {
                $this->merge([$key => []]);
            } else {
                $this->merge([
                    $key => array_values(array_filter(array_map(
                        static fn ($id) => (int) $id,
                        (array) $value,
                    ))),
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type_code' => ['required', Rule::enum(BookTypeEnum::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'authors' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'string', 'max:255'],
            'publisher' => ['required', 'string', 'max:255'],
            'pub_year' => ['required', 'integer', 'digits:4', 'min:1000', 'max:9999'],
            'edition' => ['nullable', 'string', 'max:255'],
            'language' => ['required', 'string', 'max:255'],
            'pages' => ['required_if:type_code,PHYSICAL', 'nullable', 'integer', 'min:1'],
            'physical_book' => ['required_if:type_code,PHYSICAL', 'array'],
            'physical_book.shelf_id' => [
                'required_with:physical_book',
                'integer',
                'exists:'.SchemaTable::forValidation('book_catalog.shelves').',id',
            ],
            'physical_book.code_no' => [
                'required_with:physical_book',
                'string',
                'max:255',
                Rule::unique(SchemaTable::forValidation('book_catalog.physical_books'), 'code_no'),
            ],
            'physical_book.copies' => ['required_with:physical_book', 'integer', 'min:1'],
            'file' => [
                'required_if:type_code,DIGITAL',
                'file',
                'mimes:pdf,epub',
                'max:102400',
            ],
            'digital_book' => ['required_if:type_code,DIGITAL', 'array'],
            'digital_book.file_cover' => ['prohibited'],
            'digital_book.file_path' => ['prohibited'],
            'digital_book.file_name' => ['prohibited'],
            'digital_book.file_type' => ['prohibited'],
            'digital_book.file_size' => ['prohibited'],
            'digital_book.format' => ['prohibited'],
            'digital_book.is_downloadable' => ['sometimes', 'boolean'],
            'digital_book.is_active' => ['sometimes', 'boolean'],
            'digital_book.checksum' => ['prohibited'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'exists:'.SchemaTable::forValidation('book_catalog.categories').',id'],
            'sub_category_ids' => ['sometimes', 'array'],
            'sub_category_ids.*' => ['integer', 'exists:'.SchemaTable::forValidation('book_catalog.sub_categories').',id'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['integer', 'exists:'.SchemaTable::forValidation('book_catalog.tags').',id'],
        ];
    }
}
