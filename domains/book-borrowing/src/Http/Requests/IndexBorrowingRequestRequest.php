<?php

namespace Modules\BookBorrowing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\BookBorrowing\Enums\BorrowingProcessCodeEnum;
use Modules\BookBorrowing\Models\BorrowingRequest;

class IndexBorrowingRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', BorrowingRequest::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tab' => ['nullable', 'string', Rule::in(['unattended', 'issued', 'all'])],
            'status_code' => [
                'nullable',
                'string',
                Rule::in(array_column(BorrowingProcessCodeEnum::cases(), 'value')),
            ],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
