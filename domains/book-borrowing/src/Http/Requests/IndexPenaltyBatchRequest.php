<?php

namespace Modules\BookBorrowing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\BookBorrowing\Enums\PenaltyBatchStatusEnum;
use Modules\BookBorrowing\Models\PenaltyBatch;

class IndexPenaltyBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', PenaltyBatch::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $statuses = array_map(
            fn (PenaltyBatchStatusEnum $case) => $case->value,
            PenaltyBatchStatusEnum::cases(),
        );

        return [
            'tab' => ['nullable', 'string', Rule::in(['all', 'pending', 'paid', 'stopped', ...$statuses])],
            'status' => ['nullable', 'string', Rule::in($statuses)],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
