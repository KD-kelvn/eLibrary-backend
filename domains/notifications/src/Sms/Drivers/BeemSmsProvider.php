<?php

namespace Modules\Notifications\Sms\Drivers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Notifications\Contracts\SmsProvider;
use Modules\Notifications\Sms\SmsHealthResult;
use Modules\Notifications\Sms\SmsResult;

/**
 * Beem Africa SMS — same contract as family-hub:
 * POST {url} with HTTP Basic (api_key:secret_key), dest_addr without "+", approved sender_id.
 *
 * @see family-hub Modules\Notifications\Sms\BeemSmsProvider
 * @see https://docs.beem.africa/api-reference/send-sms
 */
class BeemSmsProvider implements SmsProvider
{
    /**
     * @param  array{url?: string, api_key?: string, secret_key?: string, sender_id?: string, balance_url?: string}  $config
     */
    public function __construct(
        private readonly array $config,
    ) {}

    public function name(): string
    {
        return 'beem';
    }

    public function send(string $toE164, string $message, ?string $idempotencyKey = null): SmsResult
    {
        $apiKey = (string) ($this->config['api_key'] ?? '');
        $secret = (string) ($this->config['secret_key'] ?? '');
        $sender = (string) ($this->config['sender_id'] ?? '');

        if ($apiKey === '' || $secret === '' || $sender === '') {
            return SmsResult::failure($this->name(), 'Beem SMS credentials or sender_id are not configured.');
        }

        $url = (string) ($this->config['url'] ?? 'https://apisms.beem.africa/v1/send');

        try {
            $response = Http::withBasicAuth($apiKey, $secret)
                ->acceptJson()
                ->timeout(15)
                ->post($url, [
                    'source_addr' => $sender,
                    'schedule_time' => '',
                    'encoding' => 0,
                    'message' => $message,
                    'recipients' => [
                        [
                            'recipient_id' => '1',
                            'dest_addr' => ltrim($toE164, '+'),
                        ],
                    ],
                ]);
        } catch (ConnectionException $e) {
            return SmsResult::failure($this->name(), 'Beem is unreachable: '.$e->getMessage());
        }

        $body = $response->json() ?? [];
        $code = is_array($body) ? ($body['code'] ?? null) : null;
        $requestId = is_array($body) ? ($body['request_id'] ?? null) : null;
        $successful = is_array($body) && ($body['successful'] ?? false) === true;

        if (! $response->successful() || ! $successful) {
            $error = is_array($body)
                ? (string) ($body['message'] ?? $response->body())
                : $response->body();

            return SmsResult::failure(
                $this->name(),
                $error !== '' ? $error : 'Beem refused the message',
                is_array($body) ? $body : ['http' => $response->status(), 'code' => $code],
            );
        }

        $messageId = is_int($requestId) || is_string($requestId) ? (string) $requestId : null;

        return SmsResult::success(
            $this->name(),
            $messageId,
            is_array($body) ? $body : [],
        );
    }

    /**
     * Live ping: GET balance endpoint with the same Basic auth (V1-006 decision).
     * Falls back to a credentials-present check when balance_url is empty.
     */
    public function ping(): SmsHealthResult
    {
        $apiKey = (string) ($this->config['api_key'] ?? '');
        $secret = (string) ($this->config['secret_key'] ?? '');

        if ($apiKey === '' || $secret === '') {
            return SmsHealthResult::fail($this->name(), 'Beem credentials are not configured');
        }

        $balanceUrl = (string) ($this->config['balance_url']
            ?? 'https://apisms.beem.africa/public/v1/vendors/balance');

        if ($balanceUrl === '') {
            return SmsHealthResult::ok($this->name(), 'Beem credentials present');
        }

        try {
            $response = Http::withBasicAuth($apiKey, $secret)
                ->acceptJson()
                ->timeout(10)
                ->get($balanceUrl);
        } catch (ConnectionException $e) {
            return SmsHealthResult::fail($this->name(), 'Beem ping failed: '.$e->getMessage());
        }

        if (! $response->successful()) {
            return SmsHealthResult::fail(
                $this->name(),
                'Beem balance check HTTP '.$response->status(),
                ['body' => $response->body()],
            );
        }

        $body = $response->json() ?? [];

        return SmsHealthResult::ok(
            $this->name(),
            'Beem reachable',
            is_array($body) ? $body : ['body' => $response->body()],
        );
    }
}
