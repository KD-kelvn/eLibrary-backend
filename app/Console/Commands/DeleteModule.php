<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class DeleteModule extends Command
{
    protected $signature = 'module:delete {name}';

    protected $description = 'Delete a module and clear all references from composer.json, composer.lock, and vendor directory';

    public function handle()
    {
        $moduleName = $this->argument('name');
        $modulePath = base_path("domains/{$moduleName}");
        $vendorPath = base_path("vendor/modules/{$moduleName}");

        // Check if the module exists
        if (! File::exists($modulePath)) {
            $this->error("Module {$moduleName} does not exist.");

            return;
        }

        // Delete the module directory
        File::deleteDirectory($modulePath);
        $this->info("Module {$moduleName} deleted successfully.");

        // Remove module from vendor directory
        if (File::exists($vendorPath)) {
            File::deleteDirectory($vendorPath);
            $this->info("Removed {$moduleName} from vendor directory.");
        }

        // Remove module from composer.json
        $this->removeFromComposerJson($moduleName);

        // Remove module from composer.lock
        $this->removeFromComposerLock($moduleName);

        // Clear the modules cache
        Artisan::call('modules:clear');
        $this->info('Cache cleared successfully.');
    }

    protected function removeFromComposerJson($moduleName)
    {
        $composerPath = base_path('composer.json');
        $composer = json_decode(File::get($composerPath), true);

        // Remove the module from the require section
        if (isset($composer['require']["modules/{$moduleName}"])) {
            unset($composer['require']["modules/{$moduleName}"]);
            File::put($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info("Removed {$moduleName} from composer.json.");
        }
    }

    protected function removeFromComposerLock($moduleName)
    {
        $lockPath = base_path('composer.lock');
        $lock = json_decode(File::get($lockPath), true);

        // Remove the module from the packages
        foreach ($lock['packages'] as $key => $package) {
            if ($package['name'] === "modules/{$moduleName}") {
                unset($lock['packages'][$key]);
                File::put($lockPath, json_encode($lock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $this->info("Removed {$moduleName} from composer.lock.");
                break;
            }
        }
    }
}
