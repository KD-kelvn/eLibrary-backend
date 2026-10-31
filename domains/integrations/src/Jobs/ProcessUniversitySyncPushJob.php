<?php

namespace Modules\Integrations\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\Integrations\Models\SyncRun;
use Modules\Integrations\Services\SyncPushService;

class ProcessUniversitySyncPushJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<array<string, mixed>>  $students
     */
    public function __construct(
        public int $syncRunId,
        public array $students,
    ) {}

    public function handle(SyncPushService $service): void
    {
        $run = SyncRun::query()->find($this->syncRunId);
        if (! $run || $run->status === SyncRun::STATUS_COMPLETED) {
            return;
        }

        $service->processRun($run, $this->students);
    }
}
