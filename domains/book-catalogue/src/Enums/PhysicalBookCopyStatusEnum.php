<?php

namespace Modules\BookCatalogue\Enums;

enum PhysicalBookCopyStatusEnum: string
{
    case Available = 'AVAILABLE';
    case OnLoan = 'ON_LOAN';
    case Lost = 'LOST';
    case Damaged = 'DAMAGED';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::OnLoan => 'On loan',
            self::Lost => 'Lost',
            self::Damaged => 'Damaged',
        };
    }
}
