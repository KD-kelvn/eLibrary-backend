<?php

namespace App\Console\Commands;

use App\Console\Concerns\InteractsWithCommandPrompts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class DeleteModule extends Command
{
    use InteractsWithCommandPrompts;

    protected $signature = 'module:delete {name?}';

    protected $description = 'Delete a module and clear all references from composer.json, composer.lock, and vendor directory';

    public function handle(): int
    {
        $moduleName = $this->argument('name');

        if (! filled($moduleName)) {
            $modules = $this->availableModules();

            if ($modules === []) {
                $this->error('No modules found in domains/.');

                return self::FAILURE;
            }

            $moduleName = select(
                label: 'Which module should be deleted?',
                options: $modules,
                required: true,
            );
        }

        $modulePath = base_path("domains/{$moduleName}");
        $vendorPath = base_path("vendor/modules/{$moduleName}");

        if (! File::exists($modulePath)) {
            $this->error("Module {$moduleName} does not exist.");

            return self::FAILURE;
        }

        if (! confirm(
            label: "Delete module \"{$moduleName}\" and remove it from composer.json and composer.lock?",
            default: false,
            hint: 'This cannot be undone.',
        )) {
            $this->warn('Cancelled.');

            return self::INVALID;
        }

        File::deleteDirectory($modulePath);
        $this->info("Module {$moduleName} deleted successfully.");

        if (File::exists($vendorPath)) {
            File::deleteDirectory($vendorPath);
            $this->info("Removed {$moduleName} from vendor directory.");
        }

        $this->removeFromComposerJson($moduleName);
        $this->removeFromComposerLock($moduleName);

        Artisan::call('modules:clear');
        $this->info('Cache cleared successfully.');

        $this->runComposerDumpAutoload();

        $this->newLine();
        $this->comment('Run `composer update` if you need to refresh the lock file after removing the module.');

        return self::SUCCESS;
    }

    protected function removeFromComposerJson(string $moduleName): void
    {
        $composerPath = base_path('composer.json');
        $composer = $this->readJsonFile($composerPath);

        if ($composer === null) {
            return;
        }

        $packageName = "modules/{$moduleName}";

        if (! isset($composer['require'][$packageName])) {
            $this->warn("{$packageName} was not listed in composer.json require.");

            return;
        }

        unset($composer['require'][$packageName]);
        $this->writeJsonFile($composerPath, $composer);
        $this->info("Removed {$moduleName} from composer.json.");
    }

    protected function removeFromComposerLock(string $moduleName): void
    {
        $lockPath = base_path('composer.lock');
        $lock = $this->readJsonFile($lockPath);

        if ($lock === null) {
            return;
        }

        $packageName = "modules/{$moduleName}";
        $removed = false;

        foreach (['packages', 'packages-dev'] as $section) {
            if (! isset($lock[$section]) || ! is_array($lock[$section])) {
                continue;
            }

            $originalCount = count($lock[$section]);

            $lock[$section] = array_values(array_filter(
                $lock[$section],
                fn (array $package) => ($package['name'] ?? '') !== $packageName,
            ));

            if (count($lock[$section]) < $originalCount) {
                $removed = true;
            }
        }

        if (! $removed) {
            $this->warn("{$packageName} was not found in composer.lock.");

            return;
        }

        $this->writeJsonFile($lockPath, $lock);
        $this->info("Removed {$moduleName} from composer.lock.");
    }

    /** @return array<string, mixed>|null */
    protected function readJsonFile(string $path): ?array
    {
        if (! File::exists($path)) {
            $this->error("File not found: {$path}");

            return null;
        }

        $decoded = json_decode(File::get($path), true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            $this->error('Failed to parse '.basename($path).': '.json_last_error_msg());

            return null;
        }

        return $decoded;
    }

    /** @param  array<string, mixed>  $data */
    protected function writeJsonFile(string $path, array $data): void
    {
        File::put(
            $path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
        );
    }

    protected function runComposerDumpAutoload(): void
    {
        $process = new Process(['composer', 'dump-autoload'], base_path());
        $process->setTimeout(120);
        $process->run();

        if ($process->isSuccessful()) {
            $this->info('Composer autoload regenerated.');

            return;
        }

        $this->warn('Could not run composer dump-autoload automatically.');
        $this->line($process->getErrorOutput());
    }
}
