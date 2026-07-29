<?php

namespace Modules\BookCatalogue\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\BookCatalogue\Enums\BookTypeEnum;
use Modules\BookCatalogue\Enums\EbookFormatEnum;
use Modules\BookCatalogue\Models\BookDetail;

class StoreBookDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', BookDetail::class) ?? false;
    }

    /** @return array<string, mixed> */
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
            'pages' => ['required', 'integer', 'min:1'],

            'physical_book' => ['required_if:type_code,PHYSICAL', 'array'],
            'physical_book.shelf_id' => [
                'required_with:physical_book',
                'integer',
                'exists:book_catalog.shelves,id',
            ],
            'physical_book.code_no' => [
                'required_with:physical_book',
                'string',
                'max:255',
                Rule::unique('book_catalog.physical_books', 'code_no'),
            ],
            'physical_book.copies' => ['required_with:physical_book', 'integer', 'min:1'],

            'digital_book' => ['required_if:type_code,DIGITAL', 'array'],
            'digital_book.file_cover' => ['nullable', 'string', 'max:255'],
            'digital_book.file_path' => ['required_with:digital_book', 'string', 'max:255'],
            'digital_book.file_name' => ['required_with:digital_book', 'string', 'max:255'],
            'digital_book.file_type' => ['required_with:digital_book', 'string', 'max:100'],
            'digital_book.file_size' => ['required_with:digital_book', 'integer', 'min:0'],
            'digital_book.format' => [
                'required_with:digital_book',
                Rule::enum(EbookFormatEnum::class),
            ],
            'digital_book.is_downloadable' => ['sometimes', 'boolean'],
            'digital_book.is_active' => ['sometimes', 'boolean'],
            'digital_book.checksum' => ['nullable', 'string', 'max:64'],

            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'exists:book_catalog.categories,id'],
            'sub_category_ids' => ['sometimes', 'array'],
            'sub_category_ids.*' => ['integer', 'exists:book_catalog.sub_categories,id'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['integer', 'exists:book_catalog.tags,id'],
        ];
    }
}
