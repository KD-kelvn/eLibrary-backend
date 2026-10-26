<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Authentication\Models\User;

class SetUserActiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = User::query()->find($this->route('user'));

        return $user && ($this->user()?->can('update', $user) ?? false);
    }

    public function rules(): array
    {
        return [
            'active' => ['required', 'boolean'],
            'reason' => ['required_if:active,false', 'nullable', 'string', 'max:1000'],
        ];
    }
}
