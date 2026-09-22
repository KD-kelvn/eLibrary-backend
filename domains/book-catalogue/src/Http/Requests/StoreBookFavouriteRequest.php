<?php

namespace Modules\BookCatalogue\Http\Requests;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Modules\BookCatalogue\Models\BookFavourite;

class StoreBookFavouriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', BookFavourite::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'book_detail_id' => [
                'required',
                'integer',
                'exists:'.SchemaTable::forValidation('book_catalog.book_details').',id',
            ],
        ];
    }
}
