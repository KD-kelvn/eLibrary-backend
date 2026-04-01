<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class CustomMigrate extends Command
{
    protected $signature = 'custom-migrate {--module=} {--group=} {--force} {--pretend}';

    protected $description = 'Run migrations for a specific domain and optional group folder';

    public function handle(): void
    {
        $domain = $this->option('module');
        $group = $this->option('group');
        $force = $this->option('force');
        $pretend = $this->option('pretend');
        if (! $domain && ! $group) {
            $this->error('Module name or group name is required. Use --module or --group option.');

            return;
        }

        if ($domain) {
            $domainPath = base_path("domains/{$domain}");

            if (! File::exists($domainPath)) {
                $this->error("Domain '{$domain}' does not exist.");

                return;
            }
            $migrationsPath = "domains/{$domain}/database/migrations";
            if ($group) {
                $migrationsPath = "domains/{$domain}/database/migrations/{$group}";
            }
        }

        if ($group && ! $domain) {
            $migrationsPath = base_path("database/migrations/{$group}");
        }

        $this->info("Running migrations from: {$migrationsPath}");

        // Build the migrate command options
        $options = [
            '--path' => $migrationsPath,
        ];

        if ($force) {
            $options['--force'] = true;
        }

        // Run the migration
        $this->info('Migrating...');
        if ($pretend) {
            $options['--pretend'] = true;
            $exitCode = Artisan::call('migrate', $options);
        } else {
            $exitCode = Artisan::call('migrate', $options);
        }

        if ($exitCode === 0) {
            $this->info(Artisan::output());
        } else {
            $this->error('Migration failed!');
            $this->info(Artisan::output());

            return;
        }
    }
}
