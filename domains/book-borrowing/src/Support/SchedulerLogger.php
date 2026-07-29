<?php

namespace Modules\BookBorrowing\Support;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

final class SchedulerLogger
{
    public static function channel(): LoggerInterface
    {
        return Log::channel('scheduler');
    }
}
