<?php

namespace Modules\BookBorrowing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\BookBorrowing\Models\PenaltyPolicy;

class StorePenaltyPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PenaltyPolicy::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'grace_days' => ['required', 'integer', 'min:0', 'max:365'],
            'cost_per_day' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'is_active' => ['sometimes', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ];
    }
}
