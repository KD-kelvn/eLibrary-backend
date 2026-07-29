<?php

namespace Modules\BookBorrowing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\BookBorrowing\Models\PenaltyBatch;

class MarkPenaltyBatchPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = PenaltyBatch::query()->findOrFail($this->route('penalty_batch'));

        return $this->user()?->can('markPaid', $batch) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
