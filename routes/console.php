<?php

use Cron\CronExpression;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Modules\Settings\Services\SettingsService;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('health:check')
    ->everyMinute()
    ->monitorName('application-health')
    ->storeOutputInDb();

Schedule::command('health:schedule-check-heartbeat')
    ->everyMinute()
    ->monitorName('health-schedule-heartbeat');

Schedule::command('health:queue-check-heartbeat')
    ->everyMinute()
    ->monitorName('health-queue-heartbeat');

Schedule::command('activitylog:clean')
    ->daily()
    ->monitorName('activitylog-clean');

Schedule::command('model:prune', [
    '--model' => MonitoredScheduledTaskLogItem::class,
])
    ->daily()
    ->monitorName('schedule-monitor-prune');

// Matches the daily "scheduler" log channel filename (scheduler-YYYY-MM-DD.log).
$schedulerConsoleLog = storage_path('logs/scheduler-'.now()->format('Y-m-d').'.log');

Schedule::command('borrowing:create-penalty-batches')
    ->dailyAt('00:00')
    ->name('borrowing-create-penalty-batches')
    ->monitorName('borrowing-create-penalty-batches')
    ->withoutOverlapping()
    ->appendOutputTo($schedulerConsoleLog)
    ->storeOutputInDb();

Schedule::command('borrowing:generate-daily-penalties')
    ->dailyAt('00:10')
    ->name('borrowing-generate-daily-penalties')
    ->monitorName('borrowing-generate-daily-penalties')
    ->withoutOverlapping()
    ->appendOutputTo($schedulerConsoleLog)
    ->storeOutputInDb();

// FR-SET-09 — propagate future-dated library settings within ~60s.
Schedule::command('settings:activate-pending')
    ->everyMinute()
    ->name('settings-activate-pending')
    ->monitorName('settings-activate-pending')
    ->withoutOverlapping();

// FR-SYNC-02 — pull when sync.pull_cron matches (dispatcher every minute; SDD §4 / §12).
Schedule::command('university:sync-pull')
    ->everyMinute()
    ->name('university-sync-pull')
    ->monitorName('university-sync-pull')
    ->withoutOverlapping()
    ->when(function () {
        if (! app(SettingsService::class)->get('sync.pull_enabled')) {
            return false;
        }

        $expression = (string) app(SettingsService::class)->get('sync.pull_cron');

        try {
            return (new CronExpression($expression))->isDue(now());
        } catch (Throwable) {
            return false;
        }
    })
    ->appendOutputTo($schedulerConsoleLog)
    ->storeOutputInDb();
