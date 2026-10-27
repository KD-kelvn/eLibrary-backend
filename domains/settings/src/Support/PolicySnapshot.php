<?php

namespace Modules\Settings\Support;

/**
 * Role-resolved policy values commonly needed by circulation consumers.
 */
final class PolicySnapshot
{
    public function __construct(
        public readonly string $roleCode,
        public readonly int $loanPeriodDays,
        public readonly int $maxConcurrentLoans,
        public readonly int $maxRenewals,
        public readonly float $fineRatePerDay,
        public readonly int $graceDays,
        public readonly float $fineCapPerLoan,
        public readonly float $blockThreshold,
        public readonly int $pickupWindowDays,
        public readonly int $holdCollectWindowDays,
        public readonly int $maxActiveHolds,
    ) {}

    /**
     * @return array<string, int|float|string>
     */
    public function toArray(): array
    {
        return [
            'roleCode' => $this->roleCode,
            'loanPeriodDays' => $this->loanPeriodDays,
            'maxConcurrentLoans' => $this->maxConcurrentLoans,
            'maxRenewals' => $this->maxRenewals,
            'fineRatePerDay' => $this->fineRatePerDay,
            'graceDays' => $this->graceDays,
            'fineCapPerLoan' => $this->fineCapPerLoan,
            'blockThreshold' => $this->blockThreshold,
            'pickupWindowDays' => $this->pickupWindowDays,
            'holdCollectWindowDays' => $this->holdCollectWindowDays,
            'maxActiveHolds' => $this->maxActiveHolds,
        ];
    }
}
