<?php

namespace App\Console\Commands;

use App\Console\Concerns\InteractsWithCommandPrompts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\select;

class CustomMigrate extends Command
{
    use InteractsWithCommandPrompts;

    protected $signature = 'custom-migrate {--module=} {--group=} {--force} {--pretend}';

    protected $description = 'Run migrations for a specific domain and optional group folder';

    public function handle(): int
    {
        $domain = $this->option('module') ?: null;
        $group = $this->option('group') ?: null;
        $force = $this->option('force');
        $pretend = $this->option('pretend');

        if (! $domain && ! $group) {
            $target = $this->promptMigrationTarget();

            if ($target === 'app') {
                $appGroups = $this->migrationGroupsForApp();
                $group = $this->promptMigrationGroup(null, $appGroups);
                $migrationsPath = $group
                    ? database_path("migrations/{$group}")
                    : database_path('migrations');
            } else {
                $modules = $this->availableModules();

                if ($modules === []) {
                    $this->error('No domain modules found.');

                    return self::FAILURE;
                }

                $domain = select(
                    label: 'Which domain should be migrated?',
                    options: $modules,
                    required: true,
                );
            }
        }

        if ($domain && ! $this->ensureModuleExists($domain)) {
            return self::FAILURE;
        }

        if ($domain && ! isset($migrationsPath)) {
            if (! filled($group)) {
                $group = $this->promptMigrationGroup(null, $this->migrationGroupsForDomain($domain));
            }

            $migrationsPath = base_path("domains/{$domain}/database/migrations");

            if ($group) {
                $migrationsPath .= "/{$group}";
            }
        } elseif ($group && ! isset($migrationsPath)) {
            $migrationsPath = database_path("migrations/{$group}");
        }

        if (! isset($migrationsPath) || ! File::isDirectory($migrationsPath)) {
            $this->error('Migration path does not exist.');

            return self::FAILURE;
        }

        $relativePath = str_replace(base_path().'/', '', $migrationsPath);
        $this->info("Running migrations from: {$relativePath}");

        $options = [
            '--path' => $relativePath,
        ];

        if ($force) {
            $options['--force'] = true;
        }

        if ($pretend) {
            $options['--pretend'] = true;
        }

        $this->info('Migrating...');
        $exitCode = Artisan::call('migrate', $options);

        if ($exitCode === 0) {
            $this->info(Artisan::output());

            return self::SUCCESS;
        }

        $this->error('Migration failed!');
        $this->info(Artisan::output());

        return self::FAILURE;
    }
}
