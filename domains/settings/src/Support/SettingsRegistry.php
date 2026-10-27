<?php

namespace Modules\Settings\Support;

use InvalidArgumentException;

/**
 * Read-only view of config/library-settings.php (Library policy §8).
 */
class SettingsRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return config('library-settings.keys', []);
    }

    /**
     * @return list<string>
     */
    public function groups(): array
    {
        return config('library-settings.groups', []);
    }

    /**
     * @return list<array{rule: string, keys: list<string>, message: string}>
     */
    public function crossFieldRules(): array
    {
        return config('library-settings.cross_field', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(string $key): array
    {
        $definition = $this->all()[$key] ?? null;

        if ($definition === null) {
            throw new InvalidArgumentException("Unknown library setting key [{$key}].");
        }

        return $definition;
    }

    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function isRoleScoped(string $key): bool
    {
        return ($this->definition($key)['scope'] ?? 'global') === 'role';
    }

    /**
     * Registry default for a key. Role-scoped keys require a role code.
     */
    public function default(string $key, ?string $roleCode = null): mixed
    {
        $definition = $this->definition($key);
        $default = $definition['default'];

        if (($definition['scope'] ?? 'global') === 'role') {
            $role = $roleCode ?? 'student';

            if (! is_array($default) || ! array_key_exists($role, $default)) {
                throw new InvalidArgumentException(
                    "No default for setting [{$key}] and role [{$role}].",
                );
            }

            return $default[$role];
        }

        return $default;
    }

    /**
     * Laravel-style validation rule hints for a key (used by V1-001a Form Requests).
     *
     * @return list<string>
     */
    public function rules(string $key): array
    {
        $definition = $this->definition($key);
        $type = $definition['type'] ?? 'string';
        $rules = ['required'];

        $extra = match ($type) {
            'int' => array_values(array_filter([
                'integer',
                isset($definition['min']) ? 'min:'.$definition['min'] : null,
                isset($definition['max']) ? 'max:'.$definition['max'] : null,
            ])),
            'money' => array_values(array_filter([
                'numeric',
                isset($definition['min']) ? 'min:'.$definition['min'] : null,
                isset($definition['max']) ? 'max:'.$definition['max'] : null,
            ])),
            'bool' => ['boolean'],
            'enum' => [
                'string',
                'in:'.implode(',', $definition['allowed'] ?? []),
            ],
            'list', 'list_int' => ['array'],
            'time' => ['date_format:H:i'],
            'cron' => ['string'],
            'object', 'map', 'time_range' => ['array'],
            default => ['string'],
        };

        return array_merge($rules, $extra);
    }

    /**
     * Normalize a storage scope string.
     */
    public function normalizeScope(string $key, ?string $roleCode = null): string
    {
        if ($this->isRoleScoped($key)) {
            $role = $roleCode ?? 'student';

            return 'role:'.$role;
        }

        return 'global';
    }

    /**
     * Parse role code from a scope string, or null for global.
     */
    public function roleFromScope(string $scope): ?string
    {
        if ($scope === 'global') {
            return null;
        }

        if (str_starts_with($scope, 'role:')) {
            return substr($scope, 5);
        }

        throw new InvalidArgumentException("Invalid setting scope [{$scope}].");
    }
}
