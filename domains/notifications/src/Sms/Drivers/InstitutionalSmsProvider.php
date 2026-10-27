<?php

namespace Modules\Notifications\Sms\Drivers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Modules\Notifications\Contracts\SmsProvider;
use Modules\Notifications\Sms\SmsHealthResult;
use Modules\Notifications\Sms\SmsResult;

/**
 * Generic HTTP adapter for a university / government central SMS gateway.
 * Payload shape is configurable; defaults match a common JSON contract.
 *
 * @phpstan-type InstitutionalConfig array{
 *   base_url?: string,
 *   send_path?: string,
 *   health_url?: string,
 *   api_key?: string,
 *   api_key_header?: string,
 *   sender_id?: string,
 *   to_field?: string,
 *   message_field?: string,
 *   sender_field?: string,
 *   auth_scheme?: string
 * }
 */
class InstitutionalSmsProvider implements SmsProvider
{
    /**
     * @param  InstitutionalConfig  $config
     */
    public function __construct(
        private readonly array $config,
    ) {}

    public function name(): string
    {
        return 'institutional';
    }

    public function send(string $toE164, string $message, ?string $idempotencyKey = null): SmsResult
    {
        $baseUrl = rtrim((string) ($this->config['base_url'] ?? ''), '/');
        if ($baseUrl === '') {
            return SmsResult::failure($this->name(), 'Institutional SMS base_url is not configured.');
        }

        $sendPath = (string) ($this->config['send_path'] ?? '/sms/send');
        $toField = (string) ($this->config['to_field'] ?? 'to');
        $messageField = (string) ($this->config['message_field'] ?? 'message');
        $senderField = (string) ($this->config['sender_field'] ?? 'sender_id');
        $sender = (string) ($this->config['sender_id'] ?? '');

        $payload = [
            $toField => $toE164,
            $messageField => $message,
        ];

        if ($sender !== '') {
            $payload[$senderField] = $sender;
        }

        if ($idempotencyKey) {
            $payload['idempotency_key'] = $idempotencyKey;
        }

        try {
            $response = $this->client()
                ->timeout(15)
                ->post($baseUrl.$sendPath, $payload);
        } catch (ConnectionException $e) {
            return SmsResult::failure($this->name(), 'Institutional SMS connection failed: '.$e->getMessage());
        }

        $body = $response->json() ?? [];
        $messageId = is_array($body)
            ? ($body['message_id'] ?? $body['id'] ?? $body['reference'] ?? null)
            : null;

        if (! $response->successful()) {
            $error = is_array($body)
                ? (string) ($body['message'] ?? $body['error'] ?? $response->body())
                : $response->body();

            return SmsResult::failure(
                $this->name(),
                $error !== '' ? $error : 'Institutional SMS send failed',
                is_array($body) ? $body : [],
            );
        }

        return SmsResult::success(
            $this->name(),
            is_string($messageId) ? $messageId : (is_numeric($messageId) ? (string) $messageId : null),
            is_array($body) ? $body : [],
        );
    }

    public function ping(): SmsHealthResult
    {
        $healthUrl = (string) ($this->config['health_url'] ?? '');
        $baseUrl = rtrim((string) ($this->config['base_url'] ?? ''), '/');

        if ($healthUrl === '' && $baseUrl === '') {
            return SmsHealthResult::fail($this->name(), 'Institutional SMS is not configured');
        }

        $url = $healthUrl !== '' ? $healthUrl : $baseUrl.'/health';

        try {
            $response = $this->client()->timeout(10)->get($url);
        } catch (ConnectionException $e) {
            return SmsHealthResult::fail($this->name(), 'Institutional ping failed: '.$e->getMessage());
        }

        if (! $response->successful()) {
            return SmsHealthResult::fail(
                $this->name(),
                'Institutional health HTTP '.$response->status(),
                ['body' => $response->body()],
            );
        }

        return SmsHealthResult::ok($this->name(), 'Institutional SMS gateway reachable');
    }

    /**
     * @return PendingRequest
     */
    protected function client()
    {
        $request = Http::acceptJson()->asJson();
        $apiKey = (string) ($this->config['api_key'] ?? '');
        $header = (string) ($this->config['api_key_header'] ?? 'X-Api-Key');
        $scheme = strtolower((string) ($this->config['auth_scheme'] ?? 'header'));

        if ($apiKey === '') {
            return $request;
        }

        return match ($scheme) {
            'bearer' => $request->withToken($apiKey),
            'basic' => $request->withBasicAuth($apiKey, (string) ($this->config['api_secret'] ?? '')),
            default => $request->withHeaders([$header => $apiKey]),
        };
    }
}
