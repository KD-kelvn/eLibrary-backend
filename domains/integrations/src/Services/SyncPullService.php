<?php

namespace Modules\Integrations\Services;

use App\Support\AdminActivity;
use Modules\Integrations\Exceptions\IntegrationsException;
use Modules\Integrations\Jobs\ProcessUniversitySyncPullJob;
use Modules\Integrations\Models\SyncRun;
use Modules\Settings\Services\SettingsService;

class SyncPullService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly UniversityCoreClient $core,
        private readonly SyncBatchProcessor $processor,
    ) {}

    /**
     * Queue a pull run (admin "run now" or scheduler).
     *
     * @return array{run_id: int, status: string}
     */
    public function queuePull(?int $triggeredBy = null, bool $force = false): array
    {
        if (! $force && ! (bool) $this->settings->get('sync.pull_enabled')) {
            throw IntegrationsException::forbidden('University pull sync is disabled.');
        }

        $cursor = $this->lastSuccessfulCursor();

        $run = SyncRun::query()->create([
            'mode' => SyncRun::MODE_PULL,
            'status' => SyncRun::STATUS_QUEUED,
            'cursor' => $cursor,
            'triggered_by' => $triggeredBy,
        ]);

        ProcessUniversitySyncPullJob::dispatch($run->id);

        AdminActivity::log(
            'University sync pull queued',
            $run,
            [
                'mode' => SyncRun::MODE_PULL,
                'cursor' => $cursor,
                'triggered_by' => $triggeredBy,
            ],
            logName: 'integrations',
            event: 'sync.run',
        );

        return [
            'run_id' => $run->id,
            'status' => $run->status,
        ];
    }

    public function processRun(SyncRun $run): void
    {
        $updatedSince = $run->cursor;
        $page = 1;
        $all = [];
        $maxUpdatedAt = $updatedSince;

        try {
            do {
                $chunk = $this->core->fetchStudentsPage($updatedSince, $page);
                foreach ($chunk['data'] as $row) {
                    $all[] = $row;
                    $rowUpdated = $row['updated_at'] ?? null;
                    if (is_string($rowUpdated) && ($maxUpdatedAt === null || $rowUpdated > $maxUpdatedAt)) {
                        $maxUpdatedAt = $rowUpdated;
                    }
                }
                $next = $chunk['next_page'] ?? null;
                $page = is_int($next) && $next > 0 ? $next : 0;
            } while ($page > 0);
        } catch (IntegrationsException $e) {
            $this->processor->failRun($run, $e->getMessage());

            return;
        } catch (\Throwable $e) {
            $this->processor->failRun($run, $e->getMessage());

            return;
        }

        $this->processor->process($run, $all);

        $run->refresh();
        if ($run->status === SyncRun::STATUS_COMPLETED && filled($maxUpdatedAt)) {
            $run->update(['cursor' => $maxUpdatedAt]);
        }
    }

    public function lastSuccessfulCursor(): ?string
    {
        $last = SyncRun::query()
            ->where('mode', SyncRun::MODE_PULL)
            ->where('status', SyncRun::STATUS_COMPLETED)
            ->whereNotNull('cursor')
            ->orderByDesc('finished_at')
            ->orderByDesc('id')
            ->first();

        return $last?->cursor;
    }

    public function lastSuccessfulAt(): ?string
    {
        $last = SyncRun::query()
            ->where('status', SyncRun::STATUS_COMPLETED)
            ->orderByDesc('finished_at')
            ->orderByDesc('id')
            ->first();

        return $last?->finished_at?->toIso8601String();
    }
}
