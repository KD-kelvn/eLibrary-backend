<?php

namespace App\Console\Commands;

use App\Console\Concerns\GeneratesScaffoldFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeCustomTrait extends Command
{
    use GeneratesScaffoldFiles;

    protected $signature = 'make:custom-trait {name?} {--module=} {--force} {--demo}';

    protected $description = 'Create a new trait with optional module support and demo methods';

    public function handle(): int
    {
        $name = $this->promptName($this->argument('name'), 'Trait name', 'e.g. Filterable');
        $module = $this->promptModule($this->option('module'));
        $force = $this->option('force');
        $demo = $this->promptDemoMethods($this->option('demo'));

        if (! $this->ensureModuleExists($module)) {
            return self::FAILURE;
        }

        // Ensure the name ends with "Trait" for consistency
        if (! Str::endsWith($name, 'Trait')) {
            $name = $name.'Trait';
        }

        // Handle nested traits (e.g., Helpers/FilterableTrait)
        $traitName = Str::studly(class_basename($name));
        $namespacePath = Str::studly(dirname($name));

        if ($module) {
            $modulePath = base_path("domains/{$module}");
            $namespace = 'Modules\\'.Str::studly($module).'\\Traits'.
                ($namespacePath !== '.' ? '\\'.str_replace('/', '\\', $namespacePath) : '');
            $path = $modulePath.'/src/Traits/'.str_replace('\\', '/', $namespacePath);
        } else {
            $namespace = 'App\Traits'.($namespacePath !== '.' ? '\\'.str_replace('/', '\\', $namespacePath) : '');
            $path = app_path('Traits/'.str_replace('\\', '/', $namespacePath));
        }

        if (! File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $filePath = $path.'/'.$traitName.'.php';
        $existedBefore = File::exists($filePath);

        if (! $this->writeScaffoldFile($path, "{$traitName}.php", $this->getTraitStub($traitName, $namespace, $demo), $force)) {
            return $existedBefore ? self::INVALID : self::FAILURE;
        }

        $this->info('Trait created successfully!');

        return self::SUCCESS;
    }

    private function getTraitStub(string $traitName, string $namespace, bool $demo): string
    {
        $content = "<?php\n\nnamespace {$namespace};\n\n";

        if ($demo) {
            $content .= "use Illuminate\Database\Eloquent\Builder;\n";
            $content .= "use Illuminate\Support\Carbon;\n\n";
        }

        $content .= "trait {$traitName}\n{\n";

        if ($demo) {
            $content .= $this->getDemoMethods();
        }

        $content .= "}\n";

        return $content;
    }

    private function getDemoMethods(): string
    {
        return <<<'EOT'
    /**
     * Scope a query to only include active records.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include records created within date range.
     */
    public function scopeCreatedBetween(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
        ]);
    }

    /**
     * Get the record's status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Active',
            'pending' => 'Pending',
            'inactive' => 'Inactive',
            default => 'Unknown',
        };
    }

EOT;
    }
}
