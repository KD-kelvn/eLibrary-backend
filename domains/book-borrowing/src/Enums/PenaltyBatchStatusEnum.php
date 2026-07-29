<?php

namespace Modules\BookBorrowing\Enums;

enum PenaltyBatchStatusEnum: string
{
    case Pending = 'PENDING';
    case Paid = 'PAID';
    case Stopped = 'STOPPED';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Stopped => 'Stopped',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'orange',
            self::Paid => 'green',
            self::Stopped => 'gray',
        };
    }
}
