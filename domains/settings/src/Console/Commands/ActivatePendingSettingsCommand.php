<?php

namespace Modules\Settings\Console\Commands;

use Illuminate\Console\Command;
use Modules\Settings\Services\SettingsService;
use Throwable;

class ActivatePendingSettingsCommand extends Command
{
    protected $signature = 'settings:activate-pending';

    protected $description = 'Bump library settings cache when future-dated values become effective (FR-SET-09)';

    public function handle(SettingsService $settings): int
    {
        try {
            $bumped = $settings->activatePending();

            if ($bumped === 0) {
                $this->comment('No pending settings became effective.');
            } else {
                $this->info('Settings cache version bumped for newly effective values.');
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
