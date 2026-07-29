<?php

namespace Modules\BookBorrowing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\BookBorrowing\Models\PenaltyPolicy;

class IndexPenaltyPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', PenaltyPolicy::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
