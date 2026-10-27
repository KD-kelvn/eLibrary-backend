<?php

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Settings\Support\LibrarySettingsAuthorizer;
use Modules\Settings\Support\SettingsRegistry;

class ResetLibrarySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(LibrarySettingsAuthorizer::class)->canReset($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $groups = app(SettingsRegistry::class)->groups();

        return [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'effective_from' => ['sometimes', 'nullable', 'date'],
            'keys' => ['sometimes', 'array', 'min:1'],
            'keys.*' => ['string'],
            'group' => ['sometimes', 'nullable', 'string', Rule::in($groups)],
            'all' => ['sometimes', 'boolean'],
            'role' => ['sometimes', 'nullable', 'string', Rule::in(['student', 'faculty', 'librarian', 'admin'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasKeys = filled($this->input('keys'));
            $hasGroup = filled($this->input('group'));
            $all = filter_var($this->input('all'), FILTER_VALIDATE_BOOLEAN);

            if (! $hasKeys && ! $hasGroup && ! $all) {
                $validator->errors()->add('keys', 'Provide keys, group, or all=true.');
            }

            $registry = app(SettingsRegistry::class);
            foreach ($this->input('keys', []) as $index => $key) {
                if (! $registry->exists((string) $key)) {
                    $validator->errors()->add("keys.{$index}", "Unknown setting key [{$key}].");
                }
            }
        });
    }
}
