<?php

namespace Modules\Notifications\Sms;

final class SmsHealthResult
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly bool $ok,
        public readonly string $provider,
        public readonly string $message,
        public readonly array $meta = [],
    ) {}

    public static function ok(string $provider, string $message = 'ok', array $meta = []): self
    {
        return new self(true, $provider, $message, $meta);
    }

    public static function fail(string $provider, string $message, array $meta = []): self
    {
        return new self(false, $provider, $message, $meta);
    }
}
