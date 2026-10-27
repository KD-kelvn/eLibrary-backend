<?php

namespace Modules\Notifications\Sms\Drivers;

use Illuminate\Support\Facades\Log;
use Modules\Notifications\Contracts\SmsProvider;
use Modules\Notifications\Sms\SmsHealthResult;
use Modules\Notifications\Sms\SmsResult;

/**
 * Development / CI SMS sink — writes to the application log.
 */
class LogSmsProvider implements SmsProvider
{
    public function name(): string
    {
        return 'log';
    }

    public function send(string $toE164, string $message, ?string $idempotencyKey = null): SmsResult
    {
        $id = 'log-'.uniqid('', true);

        Log::info('SMS (log provider)', [
            'to' => $toE164,
            'message' => $message,
            'idempotency_key' => $idempotencyKey,
            'provider_message_id' => $id,
        ]);

        return SmsResult::success($this->name(), $id);
    }

    public function ping(): SmsHealthResult
    {
        return SmsHealthResult::ok($this->name(), 'Log SMS provider is ready');
    }
}
