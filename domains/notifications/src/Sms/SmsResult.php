<?php

namespace Modules\Notifications\Sms;

final class SmsResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly bool $ok,
        public readonly string $provider,
        public readonly ?string $providerMessageId = null,
        public readonly ?string $error = null,
        public readonly array $raw = [],
    ) {}

    public static function success(string $provider, ?string $providerMessageId = null, array $raw = []): self
    {
        return new self(true, $provider, $providerMessageId, null, $raw);
    }

    public static function failure(string $provider, string $error, array $raw = []): self
    {
        return new self(false, $provider, null, $error, $raw);
    }
}
