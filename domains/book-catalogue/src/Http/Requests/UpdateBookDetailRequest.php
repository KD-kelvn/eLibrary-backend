<?php

namespace Modules\BookCatalogue\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\BookCatalogue\Enums\BookTypeEnum;
use Modules\BookCatalogue\Enums\EbookFormatEnum;
use Modules\BookCatalogue\Models\BookDetail;

class UpdateBookDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $bookDetail = BookDetail::query()->findOrFail($this->route('book'));

        return (bool) $this->user()?->can('update', $bookDetail);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var BookDetail $bookDetail */
        $bookDetail = BookDetail::query()->findOrFail($this->route('book'));
        $physicalBookId = $bookDetail->physicalBooks()->value('id');

        return [
            'type_code' => ['sometimes', 'required', Rule::enum(BookTypeEnum::class)],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'authors' => ['sometimes', 'required', 'string', 'max:255'],
            'isbn' => ['sometimes', 'required', 'string', 'max:255'],
            'publisher' => ['sometimes', 'required', 'string', 'max:255'],
            'pub_year' => ['sometimes', 'required', 'integer', 'digits:4', 'min:1000', 'max:9999'],
            'edition' => ['sometimes', 'nullable', 'string', 'max:255'],
            'language' => ['sometimes', 'required', 'string', 'max:255'],
            'pages' => ['sometimes', 'required', 'integer', 'min:1'],
            'physical_book' => ['sometimes', 'nullable', 'array'],
            'physical_book.shelf_id' => [
                'required_with:physical_book',
                'integer',
                'exists:book_catalog.shelves,id',
            ],
            'physical_book.code_no' => [
                'required_with:physical_book',
                'string',
                'max:255',
                Rule::unique('book_catalog.physical_books', 'code_no')->ignore($physicalBookId),
            ],
            'physical_book.copies' => ['required_with:physical_book', 'integer', 'min:1'],
            'digital_book' => ['sometimes', 'nullable', 'array'],
            'digital_book.file_cover' => ['sometimes', 'nullable', 'string', 'max:255'],
            'digital_book.file_path' => ['sometimes', 'required_with:digital_book', 'string', 'max:255'],
            'digital_book.file_name' => ['sometimes', 'required_with:digital_book', 'string', 'max:255'],
            'digital_book.file_type' => ['sometimes', 'required_with:digital_book', 'string', 'max:100'],
            'digital_book.file_size' => ['sometimes', 'required_with:digital_book', 'integer', 'min:0'],
            'digital_book.format' => [
                'sometimes',
                'required_with:digital_book',
                Rule::enum(EbookFormatEnum::class),
            ],
            'digital_book.is_downloadable' => ['sometimes', 'boolean'],
            'digital_book.is_active' => ['sometimes', 'boolean'],
            'digital_book.checksum' => ['sometimes', 'nullable', 'string', 'max:64'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'exists:book_catalog.categories,id'],
            'sub_category_ids' => ['sometimes', 'array'],
            'sub_category_ids.*' => ['integer', 'exists:book_catalog.sub_categories,id'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['integer', 'exists:book_catalog.tags,id'],
        ];
    }
}
