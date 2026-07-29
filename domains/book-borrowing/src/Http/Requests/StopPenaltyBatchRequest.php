<?php

namespace Modules\BookBorrowing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\BookBorrowing\Models\PenaltyBatch;

class StopPenaltyBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = PenaltyBatch::query()->findOrFail($this->route('penalty_batch'));

        return $this->user()?->can('stop', $batch) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'stopped_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
