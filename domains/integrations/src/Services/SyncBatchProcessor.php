<?php

namespace Modules\Integrations\Services;

use Illuminate\Support\Facades\Notification;
use Modules\Authentication\Models\User;
use Modules\Integrations\Models\SyncRun;
use Modules\Integrations\Models\SyncRunItem;
use Modules\Integrations\Notifications\SyncRunFailedNotification;

/**
 * Shared upsert loop + counts for push and pull runs.
 */
class SyncBatchProcessor
{
    public function __construct(private readonly StudentSyncUpsertService $upsert) {}

    /**
     * @param  list<array<string, mixed>>  $students
     * @param  array<string, mixed>  $extraRunAttributes  e.g. cursor
     */
    public function process(SyncRun $run, array $students, array $extraRunAttributes = []): void
    {
        $run->update([
            'status' => SyncRun::STATUS_RUNNING,
            'started_at' => $run->started_at ?? now(),
            ...$extraRunAttributes,
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
        $success = $counts['created'] + $counts['updated'] + $counts['deactivated'] + $counts['unchanged'];

        $run->update([
            'created_count' => $counts['created'],
            'updated_count' => $counts['updated'],
            'deactivated_count' => $counts['deactivated'],
            'unchanged_count' => $counts['unchanged'],
            'failed_count' => $failed,
            'status' => $failed > 0 && $success === 0
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

    public function failRun(SyncRun $run, string $message): void
    {
        $run->update([
            'status' => SyncRun::STATUS_FAILED,
            'started_at' => $run->started_at ?? now(),
            'finished_at' => now(),
            'error_summary' => $message,
        ]);
        $run->refresh();
        $this->alertAdmins($run);
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
