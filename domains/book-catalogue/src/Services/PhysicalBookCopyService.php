<?php

namespace Modules\BookCatalogue\Services;

use Illuminate\Support\Facades\DB;
use Modules\BookCatalogue\Enums\PhysicalBookCopyStatusEnum;
use Modules\BookCatalogue\Exceptions\BookCatalogueException;
use Modules\BookCatalogue\Models\PhysicalBookCopy;

class PhysicalBookCopyService
{
    public function updateStatus(int $id, PhysicalBookCopyStatusEnum $status): PhysicalBookCopy
    {
        return DB::transaction(function () use ($id, $status) {
            $copy = PhysicalBookCopy::query()->lockForUpdate()->find($id);

            if (! $copy) {
                throw BookCatalogueException::notFound('Physical book copy');
            }

            if ($copy->status === PhysicalBookCopyStatusEnum::OnLoan) {
                throw BookCatalogueException::unprocessable(
                    'An issued copy can only be marked available by completing its return.',
                );
            }

            $copy->update(['status' => $status]);

            return $copy->refresh();
        });
    }
}
