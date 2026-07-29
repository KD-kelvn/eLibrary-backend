<?php

namespace Modules\BookBorrowing\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\BookBorrowing\Models\PenaltyPayment;

/**
 * @mixin PenaltyPayment
 */
class PenaltyPaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'borrowingPenaltyId' => $this->borrowing_penalty_id,
            'paymentDate' => $this->payment_date,
            'paymentAmount' => (float) $this->payment_amount,
            'paymentMethod' => $this->payment_method,
            'payerMobile' => $this->payer_mobile,
            'payerAcc' => $this->payer_acc,
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
