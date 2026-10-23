<?php

namespace Modules\Settings\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Settings\Services\SettingsService;
use Modules\Settings\Support\CrossFieldSettingsValidator;
use Modules\Settings\Support\LibrarySettingsAuthorizer;
use Modules\Settings\Support\SettingsRegistry;

class UpdateLibrarySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(LibrarySettingsAuthorizer::class)->canUpdate($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'effective_from' => ['sometimes', 'nullable', 'date'],
            'changes' => ['required', 'array', 'min:1'],
            'changes.*.key' => ['required', 'string'],
            'changes.*.scope' => ['required', 'string', 'regex:/^(global|role:[a-z_]+)$/'],
            'changes.*.value' => ['required'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $registry = app(SettingsRegistry::class);
            $settings = app(SettingsService::class);
            $proposed = $settings->effectiveMap();

            foreach ($this->input('changes', []) as $index => $change) {
                $key = $change['key'] ?? null;
                $scope = $change['scope'] ?? null;

                if (! $registry->exists((string) $key)) {
                    $validator->errors()->add("changes.{$index}.key", "Unknown setting key [{$key}].");

                    continue;
                }

                try {
                    $expectedScope = $registry->normalizeScope(
                        $key,
                        $registry->roleFromScope((string) $scope),
                    );
                } catch (\InvalidArgumentException $e) {
                    $validator->errors()->add("changes.{$index}.scope", $e->getMessage());

                    continue;
                }

                if ($expectedScope !== $scope) {
                    $validator->errors()->add(
                        "changes.{$index}.scope",
                        "Scope [{$scope}] is invalid for key [{$key}]; expected [{$expectedScope}].",
                    );

                    continue;
                }

                $role = $registry->roleFromScope($scope);
                $rules = $registry->rules($key);
                $fieldValidator = validator(['value' => $change['value'] ?? null], ['value' => $rules]);

                if ($fieldValidator->fails()) {
                    foreach ($fieldValidator->errors()->get('value') as $message) {
                        $validator->errors()->add("changes.{$index}.value", $message);
                    }

                    continue;
                }

                $proposed[$key.'|'.$scope] = $change['value'];
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach (app(CrossFieldSettingsValidator::class)->violations($proposed) as $violation) {
                $validator->errors()->add('changes', $violation['message']);
            }
        });
    }
}
