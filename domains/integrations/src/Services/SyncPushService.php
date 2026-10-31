<?php

namespace Modules\Integrations\Services;

use App\Support\AdminActivity;
use Modules\Integrations\Exceptions\IntegrationsException;
use Modules\Integrations\Jobs\ProcessUniversitySyncPushJob;
use Modules\Integrations\Models\SyncRun;
use Modules\Settings\Services\SettingsService;

class SyncPushService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly SyncBatchProcessor $processor,
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
        $this->processor->process($run, $students);
    }
}
