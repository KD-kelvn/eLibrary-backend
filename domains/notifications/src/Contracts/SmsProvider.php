<?php

namespace Modules\Notifications\Contracts;

use Modules\Notifications\Sms\SmsHealthResult;
use Modules\Notifications\Sms\SmsResult;

/**
 * Outbound SMS driver contract (FR-NOT-01). Beem first; institutional/log alternatives.
 */
interface SmsProvider
{
    public function name(): string;

    /**
     * @param  string  $toE164  Destination in E.164 (leading +) or national digits.
     */
    public function send(string $toE164, string $message, ?string $idempotencyKey = null): SmsResult;

    public function ping(): SmsHealthResult;
}
