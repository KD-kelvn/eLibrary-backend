<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'Email, username, or phone is required',
            'identifier.string' => 'The identifier field must be a string.',
            'identifier.max' => 'Email, username, or phone must be less than 255 characters.',
            'password.required' => 'Password is required',
            'password.string' => 'Password must be a string.',
        ];
    }
}
