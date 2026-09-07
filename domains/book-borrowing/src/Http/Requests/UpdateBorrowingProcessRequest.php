<?php

namespace Modules\BookBorrowing\Http\Requests;

use App\Support\SchemaTable;
use Illuminate\Foundation\Http\FormRequest;
use Modules\BookBorrowing\Models\BorrowingProcess;

class UpdateBorrowingProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $process = BorrowingProcess::query()->findOrFail($this->route('borrowing_process'));

        return $this->user()?->can('update', $process) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'index_no' => ['sometimes', 'required', 'integer', 'min:0'],
            'status_name' => ['sometimes', 'required', 'string', 'max:255'],
            'status_color' => ['sometimes', 'required', 'string', 'max:40'],
            'sender_role_id' => ['sometimes', 'nullable', 'integer', 'exists:'.SchemaTable::forValidation('auth.roles').',id'],
            'receiver_role_id' => ['sometimes', 'nullable', 'integer', 'exists:'.SchemaTable::forValidation('auth.roles').',id'],
            'is_final' => ['sometimes', 'boolean'],
        ];
    }
}
