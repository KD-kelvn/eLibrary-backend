<?php

namespace Modules\BookCatalogue\Http\Requests\Reusables;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\BookCatalogue\Models\Shelf;

class UpdateShelfRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shelf = Shelf::query()->find($this->route('shelf'));

        return $shelf && $this->user()?->can('update', $shelf);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $shelfId = $this->route('shelf');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique(SchemaTable::forValidation('book_catalog.shelves'), 'code')->ignore($shelfId),
            ],
            'location' => ['sometimes', 'nullable', 'string'],
            'number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'rack' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }
}
