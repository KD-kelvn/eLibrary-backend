<?php

namespace App\Console\Commands;

use App\Console\Concerns\GeneratesScaffoldFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeModelWithExtras extends Command
{
    use GeneratesScaffoldFiles;

    protected $signature = 'make:model-with-extras {name?} {--module=} {--auditable=} {--group=} {--force}';

    protected $description = 'Create a model with resource, seeder, and migration scaffolding';

    public function handle(): int
    {
        $name = $this->promptName($this->argument('name'), 'Model name', 'e.g. BookCopy');
        $module = $this->promptModule($this->option('module'));
        $auditable = $this->promptAuditableOption($this->option('auditable')) === 'YES';
        $group = $this->promptGroup($this->option('group'), 'these files');
        $force = $this->option('force');

        if (! $this->ensureModuleExists($module)) {
            return self::FAILURE;
        }

        if (Str::endsWith($name, 'Model')) {
            $this->error('The model name should not end with "Model".');

            return self::FAILURE;
        }

        $modelName = Str::studly($name);
        $groupSegment = $group ? '/'.Str::studly($group) : '';
        $migrationFileName = now()->format('Y_m_d_His').'_create_'.Str::plural(Str::snake($name)).'_table.php';

        if ($module) {
            $modulePath = base_path("domains/{$module}");
            $modelNamespace = $this->moduleNamespace($module, 'Models', $group);
            $resourceNamespace = $this->moduleNamespace($module, 'Http\Resources', $group);
            $seederNamespace = 'Modules\\'.Str::studly($module).'\\Database\\Seeders'.($group ? '\\'.Str::studly($group) : '');

            $this->writeScaffoldFile("{$modulePath}/src/Models{$groupSegment}", "{$modelName}.php", $this->modelStubContent($modelName, $modelNamespace, $auditable), $force);
            $this->writeScaffoldFile("{$modulePath}/src/Http/Resources{$groupSegment}", "{$modelName}Resource.php", $this->resourceStubContent($name, $resourceNamespace), $force);
            $this->writeScaffoldFile("{$modulePath}/database/seeders{$groupSegment}", "{$modelName}Seeder.php", $this->seederStubContent($name, $seederNamespace), $force);
            $this->writeScaffoldFile("{$modulePath}/database/migrations{$groupSegment}", $migrationFileName, $this->migrationStubContent($name, $auditable), $force);
        } else {
            $modelNamespace = $this->appNamespace('Models', $group);
            $resourceNamespace = $this->appNamespace('Http\Resources', $group);
            $seederNamespace = 'Database\\Seeders'.($group ? '\\'.Str::studly($group) : '');

            $this->writeScaffoldFile(app_path("Models{$groupSegment}"), "{$modelName}.php", $this->modelStubContent($modelName, $modelNamespace, $auditable), $force);
            $this->writeScaffoldFile(app_path("Http/Resources{$groupSegment}"), "{$modelName}Resource.php", $this->resourceStubContent($name, $resourceNamespace), $force);
            $this->writeScaffoldFile(database_path("seeders{$groupSegment}"), "{$modelName}Seeder.php", $this->seederStubContent($name, $seederNamespace), $force);
            $this->writeScaffoldFile(database_path("migrations{$groupSegment}"), $migrationFileName, $this->migrationStubContent($name, $auditable), $force);
        }

        $this->info('Scaffolding completed.');

        return self::SUCCESS;
    }
}
