<?php

namespace Modules\Integrations\Console\Commands;

use Illuminate\Console\Command;
use Modules\Integrations\Exceptions\IntegrationsException;
use Modules\Integrations\Services\SyncPullService;
use Modules\Settings\Services\SettingsService;

class UniversitySyncPullCommand extends Command
{
    protected $signature = 'university:sync-pull {--force : Ignore sync.pull_enabled}';

    protected $description = 'Pull changed students from the university core API (V1-024)';

    public function handle(SyncPullService $pull, SettingsService $settings): int
    {
        $force = (bool) $this->option('force');
        if (! $force && ! (bool) $settings->get('sync.pull_enabled')) {
            $this->info('Pull sync disabled (sync.pull_enabled=false).');

            return self::SUCCESS;
        }

        try {
            $result = $pull->queuePull(triggeredBy: null, force: $force);
            $this->info("Queued pull run #{$result['run_id']} ({$result['status']}).");

            return self::SUCCESS;
        } catch (IntegrationsException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
