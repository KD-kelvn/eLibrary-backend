<?php

namespace Modules\Settings\Support;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned cache for resolved library settings (SDD §12.4).
 *
 * Map key: settings:v{version}
 * Counter: settings:version
 */
class SettingsCache
{
    public const VERSION_KEY = 'settings:version';

    public const LAST_ACTIVATED_KEY = 'settings:last_activated_at';

    public function __construct(
        protected ?CacheRepository $store = null,
    ) {}

    protected function cache(): CacheRepository
    {
        return $this->store ?? Cache::store();
    }

    public function version(): int
    {
        return (int) $this->cache()->get(self::VERSION_KEY, 0);
    }

    public function bump(): int
    {
        if (! $this->cache()->has(self::VERSION_KEY)) {
            $this->cache()->forever(self::VERSION_KEY, 0);
        }

        return (int) $this->cache()->increment(self::VERSION_KEY);
    }

    public function mapKey(?int $version = null): string
    {
        return 'settings:v'.($version ?? $this->version());
    }

    /**
     * @param  callable(): array{values: array<string, mixed>, valid_until: string|null}  $resolver
     * @return array{values: array<string, mixed>, valid_until: string|null}
     */
    public function remember(callable $resolver): array
    {
        $version = $this->version();
        $key = $this->mapKey($version);

        /** @var array{values: array<string, mixed>, valid_until: string|null} $payload */
        $payload = $this->cache()->rememberForever($key, $resolver);

        $validUntil = $payload['valid_until'] ?? null;
        if ($validUntil !== null && Carbon::parse($validUntil)->isPast()) {
            $this->bump();
            $key = $this->mapKey();

            /** @var array{values: array<string, mixed>, valid_until: string|null} $payload */
            $payload = $this->cache()->rememberForever($key, $resolver);
        }

        return $payload;
    }

    public function flush(): void
    {
        $this->bump();
    }

    public function lastActivatedAt(): ?Carbon
    {
        $value = $this->cache()->get(self::LAST_ACTIVATED_KEY);

        return $value ? Carbon::parse($value) : null;
    }

    public function markActivatedAt(?Carbon $at = null): void
    {
        $this->cache()->forever(self::LAST_ACTIVATED_KEY, ($at ?? now())->toIso8601String());
    }
}
