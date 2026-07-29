<?php

namespace Modules\BookBorrowing\Services;

use Modules\BookBorrowing\Models\BorrowingProcess;
use Modules\BookBorrowing\Models\BorrowingProgress;
use Modules\BookBorrowing\Models\BorrowingRequest;

class BorrowingProgressService
{
    public function record(
        BorrowingRequest $request,
        BorrowingProcess $process,
        ?string $remarks = null,
        ?BorrowingProcess $nextProcess = null,
    ): BorrowingProgress {
        return $request->progresses()->create([
            'process_id' => $process->id,
            'next_process_id' => $nextProcess?->id,
            'remarks' => $remarks,
            'status_code' => $process->status_code,
            'sender_role_id' => $process->sender_role_id,
            'receiver_role_id' => $process->receiver_role_id,
        ]);
    }
}
