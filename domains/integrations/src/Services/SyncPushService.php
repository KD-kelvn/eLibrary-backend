<?php

namespace Modules\Integrations\Services;

use App\Support\AdminActivity;
use Illuminate\Support\Facades\Notification;
use Modules\Authentication\Models\User;
use Modules\Integrations\Exceptions\IntegrationsException;
use Modules\Integrations\Jobs\ProcessUniversitySyncPushJob;
use Modules\Integrations\Models\SyncRun;
use Modules\Integrations\Models\SyncRunItem;
use Modules\Integrations\Notifications\SyncRunFailedNotification;
use Modules\Settings\Services\SettingsService;

class SyncPushService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly StudentSyncUpsertService $upsert,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $students
     * @return array{run_id: int, status: string, replayed?: bool}
     */
    public function acceptPush(array $students, string $idempotencyKey): array
    {
        if (! (bool) $this->settings->get('sync.push_enabled')) {
            throw IntegrationsException::forbidden('University push sync is disabled.');
        }

        if ($students === []) {
            throw IntegrationsException::unprocessable('students batch must not be empty.');
        }

        if (count($students) > 500) {
            throw IntegrationsException::unprocessable('students batch must not exceed 500 records.');
        }

        $existing = SyncRun::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            return [
                'run_id' => $existing->id,
                'status' => $existing->status,
                'replayed' => true,
            ];
        }

        $run = SyncRun::query()->create([
            'mode' => SyncRun::MODE_PUSH,
            'status' => SyncRun::STATUS_QUEUED,
            'idempotency_key' => $idempotencyKey,
        ]);

        ProcessUniversitySyncPushJob::dispatch($run->id, $students);

        AdminActivity::log(
            'University sync push accepted',
            $run,
            [
                'mode' => SyncRun::MODE_PUSH,
                'batch_size' => count($students),
                'idempotency_key' => $idempotencyKey,
            ],
            logName: 'integrations',
            event: 'sync.run',
        );

        return [
            'run_id' => $run->id,
            'status' => $run->status,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $students
     */
    public function processRun(SyncRun $run, array $students): void
    {
        $run->update([
            'status' => SyncRun::STATUS_RUNNING,
            'started_at' => $run->started_at ?? now(),
        ]);

        $counts = [
            'created' => 0,
            'updated' => 0,
            'deactivated' => 0,
            'unchanged' => 0,
            'failed' => 0,
        ];

        foreach ($students as $record) {
            $regNo = (string) ($record['reg_no'] ?? '');

            try {
                $result = $this->upsert->upsert(is_array($record) ? $record : []);
            } catch (\Throwable $e) {
                $result = [
                    'action' => 'failed',
                    'user_id' => null,
                    'error' => $e->getMessage(),
                    'meta' => [],
                ];
            }

            $action = (string) $result['action'];
            if (isset($counts[$action])) {
                $counts[$action]++;
            } elseif ($action === 'failed') {
                $counts['failed']++;
            }

            SyncRunItem::query()->create([
                'sync_run_id' => $run->id,
                'reg_no' => $regNo !== '' ? $regNo : 'unknown',
                'action' => $action,
                'user_id' => $result['user_id'],
                'error' => $result['error'],
                'meta' => $result['meta'] ?: null,
            ]);
        }

        $failed = $counts['failed'];
        $run->update([
            'created_count' => $counts['created'],
            'updated_count' => $counts['updated'],
            'deactivated_count' => $counts['deactivated'],
            'unchanged_count' => $counts['unchanged'],
            'failed_count' => $failed,
            'status' => $failed > 0 && ($counts['created'] + $counts['updated'] + $counts['deactivated'] + $counts['unchanged']) === 0
                ? SyncRun::STATUS_FAILED
                : SyncRun::STATUS_COMPLETED,
            'finished_at' => now(),
            'error_summary' => $failed > 0 ? "{$failed} record(s) failed." : null,
        ]);

        $run->refresh();

        if ($failed > 0) {
            $this->alertAdmins($run);
        }
    }

    private function alertAdmins(SyncRun $run): void
    {
        $admins = User::query()
            ->whereHas(
                'activeUserRoles',
                fn ($query) => $query->whereHas(
                    'role',
                    fn ($role) => $role->where('code', 'admin')->where('status', 'active'),
                ),
            )
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new SyncRunFailedNotification($run));
    }
}
