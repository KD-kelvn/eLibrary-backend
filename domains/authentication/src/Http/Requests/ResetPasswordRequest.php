<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string', 'min:4', 'max:10'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
