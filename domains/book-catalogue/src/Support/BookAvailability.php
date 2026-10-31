<?php

namespace Modules\BookCatalogue\Support;

use Modules\BookCatalogue\Enums\BookTypeEnum;
use Modules\BookCatalogue\Enums\PhysicalBookCopyStatusEnum;
use Modules\BookCatalogue\Models\BookDetail;

/**
 * FR-CAT-03 / FR-COPY-04 — availability counts derived from copy statuses.
 * Holds queue length is 0 until V1-044 wires holds.
 */
final class BookAvailability
{
    /**
     * @return array{
     *     availableCopies: int,
     *     onLoanCopies: int,
     *     holdsQueueLength: int,
     *     canRead: bool,
     *     isAvailable: bool
     * }
     */
    public static function for(BookDetail $book): array
    {
        $available = self::countCopies($book, PhysicalBookCopyStatusEnum::Available, 'available_copies_count');
        $onLoan = self::countCopies($book, PhysicalBookCopyStatusEnum::OnLoan, 'on_loan_copies_count');
        $holds = (int) ($book->holds_queue_length ?? 0);
        $canRead = self::canRead($book);
        $isAvailable = $canRead || $available > 0;

        return [
            'availableCopies' => $available,
            'onLoanCopies' => $onLoan,
            'holdsQueueLength' => $holds,
            'canRead' => $canRead,
            'isAvailable' => $isAvailable,
        ];
    }

    protected static function countCopies(
        BookDetail $book,
        PhysicalBookCopyStatusEnum $status,
        string $withCountAttribute,
    ): int {
        if (isset($book->{$withCountAttribute})) {
            return (int) $book->{$withCountAttribute};
        }

        if (! $book->relationLoaded('physicalBooks')) {
            return 0;
        }

        $total = 0;
        foreach ($book->physicalBooks as $physicalBook) {
            if (! $physicalBook->relationLoaded('copyRecords')) {
                continue;
            }

            $total += $physicalBook->copyRecords
                ->where('status', $status)
                ->count();
        }

        return $total;
    }

    protected static function canRead(BookDetail $book): bool
    {
        if ($book->type_code !== BookTypeEnum::Digital) {
            return false;
        }

        if ($book->relationLoaded('digitalBook')) {
            return (bool) $book->digitalBook?->is_active;
        }

        return true;
    }
}
