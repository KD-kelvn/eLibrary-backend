<?php

namespace Modules\BookBorrowing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\BookBorrowing\Models\PenaltyPolicy;

class UpdatePenaltyPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $policy = PenaltyPolicy::query()->findOrFail($this->route('penalty_policy'));

        return $this->user()?->can('update', $policy) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'grace_days' => ['sometimes', 'required', 'integer', 'min:0', 'max:365'],
            'cost_per_day' => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'nullable', 'string', 'max:10'],
            'is_active' => ['sometimes', 'boolean'],
            'effective_from' => ['sometimes', 'nullable', 'date'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
