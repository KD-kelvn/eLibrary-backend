<?php

namespace App\Console\Commands;

use App\Console\Concerns\GeneratesScaffoldFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeControllerWithExtras extends Command
{
    use GeneratesScaffoldFiles;

    protected $signature = 'make:controller-with-extras {name?} {--module=} {--group=} {--force}';

    protected $description = 'Create a controller with repository and trait scaffolding';

    public function handle(): int
    {
        $name = $this->promptName($this->argument('name'), 'Controller name', 'e.g. Book');
        $module = $this->promptModule($this->option('module'));
        $group = $this->promptGroup($this->option('group'), 'controller, repository, and trait');
        $force = $this->option('force');

        if (! $this->ensureModuleExists($module)) {
            return self::FAILURE;
        }

        if (Str::endsWith($name, 'Controller')) {
            $this->error('The controller name should not end with "Controller".');

            return self::FAILURE;
        }

        $groupSegment = $group ? '/'.Str::studly($group) : '';

        if ($module) {
            $modulePath = base_path("domains/{$module}");
            $controllerNamespace = $this->moduleNamespace($module, 'Http\Controllers', $group);
            $repositoryNamespace = $this->moduleNamespace($module, 'Repositories', $group);
            $traitNamespace = $this->moduleNamespace($module, 'Traits', $group);

            $this->writeScaffoldFile("{$modulePath}/src/Http/Controllers{$groupSegment}", Str::studly($name).'Controller.php', $this->controllerStubContent($name, $controllerNamespace), $force);
            $this->writeScaffoldFile("{$modulePath}/src/Repositories{$groupSegment}", Str::studly($name).'Repository.php', $this->repositoryStubContent($name, $repositoryNamespace, $module), $force);
            $this->writeScaffoldFile("{$modulePath}/src/Traits{$groupSegment}", Str::studly($name).'Trait.php', $this->traitStubContent($name, $traitNamespace), $force);
        } else {
            $controllerNamespace = $this->appNamespace('Http\Controllers', $group);
            $repositoryNamespace = $this->appNamespace('Repositories', $group);
            $traitNamespace = $this->appNamespace('Traits', $group);

            $this->writeScaffoldFile(app_path("Http/Controllers{$groupSegment}"), Str::studly($name).'Controller.php', $this->controllerStubContent($name, $controllerNamespace), $force);
            $this->writeScaffoldFile(app_path("Repositories{$groupSegment}"), Str::studly($name).'Repository.php', $this->repositoryStubContent($name, $repositoryNamespace), $force);
            $this->writeScaffoldFile(app_path("Traits{$groupSegment}"), Str::studly($name).'Trait.php', $this->traitStubContent($name, $traitNamespace), $force);
        }

        $this->info('Scaffolding completed.');

        return self::SUCCESS;
    }
}
