<?php

namespace Modules\Integrations\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\Integrations\Models\SyncRun;
use Modules\Integrations\Services\SyncPullService;

class ProcessUniversitySyncPullJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $syncRunId) {}

    public function handle(SyncPullService $service): void
    {
        $run = SyncRun::query()->find($this->syncRunId);
        if (! $run || in_array($run->status, [SyncRun::STATUS_COMPLETED, SyncRun::STATUS_FAILED], true)) {
            return;
        }

        $service->processRun($run);
    }
}
