<?php

namespace Modules\BookBorrowing\Console\Commands;

use Illuminate\Console\Command;
use Modules\BookBorrowing\Services\PenaltyBatchService;

class GenerateDailyPenaltiesCommand extends Command
{
    protected $signature = 'borrowing:generate-daily-penalties {--date= : Penalty date (Y-m-d)}';

    protected $description = 'Generate daily BorrowingPenalty rows for PENDING penalty batches';

    public function handle(PenaltyBatchService $service): int
    {
        $date = $this->option('date');
        $created = $service->generateDailyPenalties($date);

        $this->info("Generated {$created} daily penalty row(s).");

        return self::SUCCESS;
    }
}
