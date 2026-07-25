<?php

namespace Modules\Authorization\Services;

use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Authentication\Models\User;
use Modules\Authorization\Models\UserRole;
use Spatie\Activitylog\Models\Activity;
use Spatie\Health\Enums\Status;
use Spatie\Health\ResultStores\ResultStore;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;

class AdminDashboardService
{
    public function summary(): array
    {
        return [
            'systemHealth' => $this->applicationHealth(),
            'users' => [
                'total' => User::query()->count(),
                'active' => User::query()->where('is_blocked', false)->count(),
                'blocked' => User::query()->where('is_blocked', true)->count(),
                'createdThisMonth' => User::query()
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->count(),
            ],
            'sessions' => [
                'totalTokens' => PersonalAccessToken::query()->count(),
                'activeToday' => PersonalAccessToken::query()
                    ->where('last_used_at', '>=', now()->startOfDay())
                    ->count(),
                'activeThisWeek' => PersonalAccessToken::query()
                    ->where('last_used_at', '>=', now()->startOfWeek())
                    ->count(),
                'lastSevenDays' => $this->sessionActivityLastSevenDays(),
            ],
            'roleAssignments' => [
                'total' => UserRole::query()->count(),
                'active' => UserRole::query()->active()->count(),
                'expiredOrRevoked' => UserRole::query()->expired()->count(),
                'expiringSoon' => UserRole::query()
                    ->active()
                    ->whereBetween('expires_at', [now(), now()->addDays(7)])
                    ->count(),
            ],
            'scheduledTasks' => $this->scheduledTasks(),
            'activity' => $this->activitySummary(),
            'database' => $this->databaseFromHealth(),
            'recentRoleAssignments' => UserRole::query()
                ->with(['user.profile', 'role', 'assignedBy.profile', 'revokedRole'])
                ->latest('assigned_at')
                ->limit(8)
                ->get(),
        ];
    }

    private function applicationHealth(): array
    {
        $results = app(ResultStore::class)->latestResults();

        if (! $results) {
            return [
                'status' => 'unknown',
                'environment' => app()->environment(),
                'phpVersion' => PHP_VERSION,
                'laravelVersion' => app()->version(),
                'checkedAt' => null,
                'okCount' => 0,
                'warningCount' => 0,
                'failedCount' => 0,
                'checks' => [],
            ];
        }

        $checks = $results->storedCheckResults->map(fn ($check) => [
            'name' => $check->name,
            'label' => $check->label,
            'status' => $check->status,
            'shortSummary' => $check->shortSummary,
            'notificationMessage' => $check->notificationMessage,
            'meta' => $check->meta,
        ])->values()->all();

        $ok = collect($checks)->where('status', Status::ok()->value)->count();
        $warning = collect($checks)->where('status', Status::warning()->value)->count();
        $failed = collect($checks)
            ->whereIn('status', [
                Status::failed()->value,
                Status::crashed()->value,
            ])
            ->count();

        $status = match (true) {
            $failed > 0 => 'unhealthy',
            $warning > 0 => 'degraded',
            default => 'healthy',
        };

        return [
            'status' => $status,
            'environment' => app()->environment(),
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => app()->version(),
            'checkedAt' => $results->finishedAt->format(DATE_ATOM),
            'okCount' => $ok,
            'warningCount' => $warning,
            'failedCount' => $failed,
            'checks' => $checks,
        ];
    }

    private function databaseFromHealth(): array
    {
        $results = app(ResultStore::class)->latestResults();
        $databaseCheck = $results?->storedCheckResults
            ->first(fn ($check) => $check->name === 'Database');

        if ($databaseCheck) {
            return [
                'status' => $databaseCheck->status === Status::ok()->value ? 'healthy' : 'unhealthy',
                'connection' => $databaseCheck->meta['connection_name'] ?? DB::getDefaultConnection(),
                'database' => DB::connection()->getDatabaseName(),
                'summary' => $databaseCheck->shortSummary ?: $databaseCheck->notificationMessage,
            ];
        }

        $startedAt = microtime(true);

        try {
            DB::select('select 1');

            return [
                'status' => 'healthy',
                'connection' => DB::getDefaultConnection(),
                'database' => DB::connection()->getDatabaseName(),
                'latencyMs' => round((microtime(true) - $startedAt) * 1000, 2),
            ];
        } catch (\Throwable $exception) {
            return [
                'status' => 'unhealthy',
                'connection' => DB::getDefaultConnection(),
                'message' => $exception->getMessage(),
            ];
        }
    }

    private function scheduledTasks(): array
    {
        $tasks = MonitoredScheduledTask::query()
            ->orderBy('name')
            ->get();

        $mapped = $tasks->map(function (MonitoredScheduledTask $task) {
            $failed = $task->last_failed_at !== null
                && (
                    $task->last_finished_at === null
                    || $task->last_failed_at->greaterThan($task->last_finished_at)
                );

            return [
                'id' => $task->id,
                'name' => $task->name,
                'type' => $task->type,
                'cronExpression' => $task->cron_expression,
                'timezone' => $task->timezone,
                'lastStartedAt' => $task->last_started_at?->toIso8601String(),
                'lastFinishedAt' => $task->last_finished_at?->toIso8601String(),
                'lastFailedAt' => $task->last_failed_at?->toIso8601String(),
                'lastSkippedAt' => $task->last_skipped_at?->toIso8601String(),
                'graceTimeInMinutes' => $task->grace_time_in_minutes,
                'status' => $failed ? 'failed' : ($task->last_finished_at ? 'healthy' : 'pending'),
            ];
        })->values();

        return [
            'total' => $mapped->count(),
            'healthy' => $mapped->where('status', 'healthy')->count(),
            'failed' => $mapped->where('status', 'failed')->count(),
            'pending' => $mapped->where('status', 'pending')->count(),
            'tasks' => $mapped->take(8)->values()->all(),
        ];
    }

    private function activitySummary(): array
    {
        $today = Activity::query()
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        $securityEvents = Activity::query()
            ->where('created_at', '>=', now()->startOfDay())
            ->where(function ($query) {
                $query
                    ->where('event', 'blocked')
                    ->orWhere('event', 'unblocked')
                    ->orWhere('description', 'like', '%blocked%')
                    ->orWhere('description', 'like', '%revoked%');
            })
            ->count();

        $recent = Activity::query()
            ->with(['causer', 'subject'])
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'logName' => $activity->log_name,
                'description' => $activity->description,
                'event' => $activity->event,
                'properties' => $activity->properties?->toArray() ?? [],
                'causer' => $activity->causer ? [
                    'id' => $activity->causer->getKey(),
                    'type' => $activity->causer_type,
                    'label' => $activity->causer->username
                        ?? $activity->causer->email
                        ?? class_basename($activity->causer),
                ] : null,
                'subject' => $activity->subject ? [
                    'id' => $activity->subject->getKey(),
                    'type' => class_basename($activity->subject_type),
                ] : null,
                'createdAt' => $activity->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return [
            'today' => $today,
            'securityEvents' => $securityEvents,
            'recent' => $recent,
        ];
    }

    private function sessionActivityLastSevenDays(): array
    {
        $days = collect(range(6, 0))->map(function (int $daysAgo) {
            $day = now()->subDays($daysAgo);

            return [
                'day' => $day->format('D'),
                'date' => $day->toDateString(),
                'count' => PersonalAccessToken::query()
                    ->whereDate('last_used_at', $day->toDateString())
                    ->count(),
            ];
        });

        $max = max(1, $days->max('count'));

        return $days->map(fn (array $item) => [
            ...$item,
            'value' => (int) round(($item['count'] / $max) * 100),
        ])->all();
    }
}
