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
