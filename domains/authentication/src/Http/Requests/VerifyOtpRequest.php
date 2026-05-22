<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Authentication\Enums\OneTimeTokenPurpose;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
            'token' => ['required', 'string', 'min:4', 'max:10'],
            'purpose' => ['sometimes', Rule::enum(OneTimeTokenPurpose::class)],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
