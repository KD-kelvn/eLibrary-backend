<?php

namespace Modules\BookBorrowing\Console\Commands;

use Illuminate\Console\Command;
use Modules\BookBorrowing\Services\PenaltyBatchService;
use Modules\BookBorrowing\Support\SchedulerLogger;
use Throwable;

class GenerateDailyPenaltiesCommand extends Command
{
    protected $signature = 'borrowing:generate-daily-penalties {--date= : Penalty date (Y-m-d)}';

    protected $description = 'Generate daily BorrowingPenalty rows for PENDING penalty batches';

    public function handle(PenaltyBatchService $service): int
    {
        $log = SchedulerLogger::channel();
        $date = $this->option('date');
        $label = $date ?: now()->toDateString();

        $log->info('Generating daily penalty rows for PENDING batches.', [
            'command' => 'borrowing:generate-daily-penalties',
            'penalty_date' => $label,
        ]);
        $this->info("[{$label}] Generating daily penalty rows for PENDING batches…");

        try {
            $created = $service->generateDailyPenalties($date ?: null);

            if ($created === 0) {
                $message = 'No new daily penalty rows generated (none due, or already created for the date).';
                $log->info($message, [
                    'penalty_date' => $label,
                    'created' => 0,
                ]);
                $this->comment($message);
            } else {
                $message = "Generated {$created} daily penalty row(s).";
                $log->info($message, [
                    'penalty_date' => $label,
                    'created' => $created,
                ]);
                $this->info($message);
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $log->error('Failed to generate daily penalties.', [
                'penalty_date' => $label,
                'exception' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
