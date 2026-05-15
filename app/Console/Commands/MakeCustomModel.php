<?php

namespace App\Console\Commands;

use App\Console\Concerns\GeneratesScaffoldFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeCustomModel extends Command
{
    use GeneratesScaffoldFiles;

    protected $signature = 'make:custom-model {name?} {--auditable} {--force} {--module=}';

    protected $description = 'Create a new model with optional auditable functionality and module support';

    public function handle(): int
    {
        $name = $this->promptName($this->argument('name'), 'Model name', 'e.g. BookCopy');
        $auditable = $this->promptAuditable($this->option('auditable'));
        $force = $this->option('force');
        $module = $this->promptModule($this->option('module'));

        if (! $this->ensureModuleExists($module)) {
            return self::FAILURE;
        }

        if (Str::endsWith($name, 'Model')) {
            $this->error('The model name should not end with "Model".');

            return self::FAILURE;
        }

        $modelName = Str::studly(class_basename($name));
        $namespacePath = $this->namespacePathFromName($name);

        if ($module) {
            $modulePath = base_path("domains/{$module}");
            $namespace = 'Modules\\'.Str::studly($module).'\\Models'.
                ($namespacePath !== '' ? '\\'.$namespacePath : '');
            $path = $modulePath.'/src/Models/'.str_replace('\\', '/', $namespacePath);
        } else {
            $namespace = 'App\\Models'.($namespacePath !== '' ? '\\'.$namespacePath : '');
            $path = app_path('Models/'.str_replace('\\', '/', $namespacePath));
        }

        $filePath = $path.'/'.$modelName.'.php';
        $existedBefore = File::exists($filePath);

        if (! $this->writeScaffoldFile($path, "{$modelName}.php", $this->modelStubContent($modelName, $namespace, $auditable), $force)) {
            return $existedBefore ? self::INVALID : self::FAILURE;
        }

        $this->info('Model created successfully!');

        return self::SUCCESS;
    }
}
