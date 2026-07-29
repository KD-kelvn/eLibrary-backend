<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
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
