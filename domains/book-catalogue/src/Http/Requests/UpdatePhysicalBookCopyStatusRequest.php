<?php

namespace Modules\BookCatalogue\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\BookCatalogue\Enums\PhysicalBookCopyStatusEnum;

class UpdatePhysicalBookCopyStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    PhysicalBookCopyStatusEnum::Available->value,
                    PhysicalBookCopyStatusEnum::Lost->value,
                    PhysicalBookCopyStatusEnum::Damaged->value,
                ]),
            ],
        ];
    }
}
