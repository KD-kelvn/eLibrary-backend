<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'challenge_id' => ['required', 'uuid'],
            'code' => ['required_without:token', 'nullable', 'string', 'min:4', 'max:10'],
            'token' => ['required_without:code', 'nullable', 'string', 'min:4', 'max:10'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
