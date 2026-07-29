<?php

namespace Modules\BookBorrowing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\BookBorrowing\Models\BorrowingProcess;

class StoreBorrowingProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', BorrowingProcess::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'index_no' => ['required', 'integer', 'min:0'],
            'status_name' => ['required', 'string', 'max:255'],
            'status_color' => ['required', 'string', 'max:40'],
            'status_code' => [
                'required',
                'string',
                'max:40',
                'alpha_dash',
                Rule::unique('book_borrowing.borrowing_processes', 'status_code'),
            ],
            'sender_role_id' => ['nullable', 'integer', 'exists:auth.roles,id'],
            'receiver_role_id' => ['nullable', 'integer', 'exists:auth.roles,id'],
            'is_final' => ['sometimes', 'boolean'],
        ];
    }
}
