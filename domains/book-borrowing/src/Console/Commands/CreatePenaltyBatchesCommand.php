<?php

namespace Modules\BookBorrowing\Console\Commands;

use Illuminate\Console\Command;
use Modules\BookBorrowing\Services\PenaltyBatchService;
use Modules\BookBorrowing\Support\SchedulerLogger;
use Throwable;

class CreatePenaltyBatchesCommand extends Command
{
    protected $signature = 'borrowing:create-penalty-batches';

    protected $description = 'Create PENDING penalty batches for overdue issued borrowings';

    public function handle(PenaltyBatchService $service): int
    {
        $log = SchedulerLogger::channel();
        $startedAt = now()->toDateTimeString();

        $log->info('Creating penalty batches for overdue issued borrowings.', [
            'command' => $this->signature,
            'started_at' => $startedAt,
        ]);
        $this->info("[{$startedAt}] Creating penalty batches for overdue issued borrowings…");

        try {
            $created = $service->createBatchesForOverdue();

            if ($created === 0) {
                $message = 'No new penalty batches created (no overdue issued borrows, or no active penalty policy).';
                $log->info($message, ['created' => 0]);
                $this->comment($message);
            } else {
                $message = "Created {$created} penalty batch(es).";
                $log->info($message, ['created' => $created]);
                $this->info($message);
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $log->error('Failed to create penalty batches.', [
                'exception' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
