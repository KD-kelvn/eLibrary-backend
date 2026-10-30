<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        // V1-011 challenge flow is preferred; legacy identifier+via kept for password-reset clients.
        return [
            'challenge_id' => ['required_without:identifier', 'nullable', 'uuid'],
            'channel' => ['required_with:challenge_id', 'nullable', Rule::in(['sms', 'email', 'phone'])],
            'identifier' => ['required_without:challenge_id', 'nullable', 'string', 'max:255'],
            'via' => ['required_with:identifier', 'nullable', Rule::in(['email', 'phone'])],
        ];
    }
}
