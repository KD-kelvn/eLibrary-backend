<?php

namespace Modules\BookBorrowing\Console\Commands;

use Illuminate\Console\Command;
use Modules\BookBorrowing\Services\PenaltyBatchService;

class CreatePenaltyBatchesCommand extends Command
{
    protected $signature = 'borrowing:create-penalty-batches';

    protected $description = 'Create PENDING penalty batches for overdue issued borrowings';

    public function handle(PenaltyBatchService $service): int
    {
        $created = $service->createBatchesForOverdue();

        $this->info("Created {$created} penalty batch(es).");

        return self::SUCCESS;
    }
}
