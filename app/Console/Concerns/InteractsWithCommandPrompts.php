<?php

namespace App\Console\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

trait InteractsWithCommandPrompts
{
    /** @return list<string> */
    protected function availableModules(): array
    {
        $domainsPath = base_path('domains');

        if (! File::isDirectory($domainsPath)) {
            return [];
        }

        return collect(File::directories($domainsPath))
            ->map(fn (string $path) => basename($path))
            ->sort()
            ->values()
            ->all();
    }

    protected function promptModule(?string $module): ?string
    {
        if (filled($module)) {
            return $module;
        }

        $modules = $this->availableModules();

        if ($modules === []) {
            return null;
        }

        $selected = select(
            label: 'Which domain module?',
            options: ['' => 'None (application root)'] + array_combine($modules, $modules),
        );

        return $selected !== '' ? $selected : null;
    }

    protected function promptName(?string $name, string $label, ?string $placeholder = null): ?string
    {
        if (filled($name)) {
            return $name;
        }

        return text(
            label: $label,
            placeholder: $placeholder,
            required: true,
            validate: fn (string $value) => preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(?:\/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$/', $value)
                ? null
                : 'Enter a valid class name (letters, numbers, underscores; use / for nested paths).',
        );
    }

    protected function promptGroup(?string $group, string $context = 'files'): ?string
    {
        if (filled($group)) {
            return $group;
        }

        if (! confirm(label: "Organize {$context} in a group folder?", default: false)) {
            return null;
        }

        return text(
            label: 'Group folder name',
            required: true,
            validate: fn (string $value) => Str::contains($value, ['/', '\\'])
                ? 'Group name cannot contain path separators.'
                : null,
        );
    }

    protected function promptAuditable(mixed $auditable): bool
    {
        if ($auditable !== null && $auditable !== false && $auditable !== '') {
            if (is_bool($auditable)) {
                return $auditable;
            }

            return in_array(strtoupper((string) $auditable), ['YES', '1', 'TRUE'], true);
        }

        return confirm(label: 'Make this model auditable?', default: false);
    }

    protected function promptAuditableOption(?string $auditable): string
    {
        if (filled($auditable)) {
            return strtoupper($auditable) === 'YES' ? 'YES' : 'NO';
        }

        return select(
            label: 'Should the model use auditing?',
            options: ['NO' => 'No', 'YES' => 'Yes'],
            default: 'NO',
        );
    }

    protected function promptDemoMethods(bool $demo): bool
    {
        if ($demo) {
            return true;
        }

        return confirm(label: 'Include demo methods?', default: false);
    }

    protected function promptOverwrite(bool $force, string $filePath): bool
    {
        if ($force) {
            return true;
        }

        if (! File::exists($filePath)) {
            return true;
        }

        return confirm(label: 'File already exists. Overwrite?', default: false);
    }

    /** @return list<string> */
    protected function migrationGroupsForDomain(string $domain): array
    {
        $path = base_path("domains/{$domain}/database/migrations");

        if (! File::isDirectory($path)) {
            return [];
        }

        return collect(File::directories($path))
            ->map(fn (string $directory) => basename($directory))
            ->sort()
            ->values()
            ->all();
    }

    /** @return list<string> */
    protected function migrationGroupsForApp(): array
    {
        $path = base_path('database/migrations');

        if (! File::isDirectory($path)) {
            return [];
        }

        return collect(File::directories($path))
            ->map(fn (string $directory) => basename($directory))
            ->sort()
            ->values()
            ->all();
    }

    protected function promptMigrationGroup(?string $group, array $groups): ?string
    {
        if (filled($group)) {
            return $group;
        }

        if ($groups === []) {
            return null;
        }

        $selected = select(
            label: 'Migration group',
            options: ['' => 'All migrations'] + array_combine($groups, $groups),
        );

        return $selected !== '' ? $selected : null;
    }

    protected function ensureModuleExists(?string $module): bool
    {
        if (! $module) {
            return true;
        }

        if (File::isDirectory(base_path("domains/{$module}"))) {
            return true;
        }

        $this->error("Module {$module} does not exist.");

        return false;
    }

    protected function promptMigrationTarget(): string
    {
        return select(
            label: 'Where should migrations run?',
            options: [
                'domain' => 'Domain module',
                'app' => 'Application (database/migrations)',
            ],
            default: 'domain',
        );
    }
}
