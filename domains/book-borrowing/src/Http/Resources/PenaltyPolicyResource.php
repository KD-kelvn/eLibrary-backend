<?php

namespace Modules\BookBorrowing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\BookBorrowing\Models\PenaltyPolicy;

/**
 * @mixin PenaltyPolicy
 */
class PenaltyPolicyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'graceDays' => $this->grace_days,
            'costPerDay' => (float) $this->cost_per_day,
            'currency' => $this->currency,
            'isActive' => $this->is_active,
            'effectiveFrom' => $this->effective_from?->toIso8601String(),
            'description' => $this->description,
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
