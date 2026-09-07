<?php

namespace App\Support\Mail;

use Illuminate\Support\Facades\Cache;
use Modules\Settings\Models\Branding;
use Modules\Settings\Services\BrandingService;
use Throwable;

class MailBranding
{
    public const CACHE_KEY = 'mail.branding.v1';

    /**
     * Shared branding payload for every transactional email.
     *
     * @return array{
     *     app_name: string,
     *     primary_color: string,
     *     secondary_color: string,
     *     tertiary_color: string,
     *     logo_url: string|null,
     *     frontend_url: string,
     * }
     */
    public function resolve(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function () {
            try {
                $branding = app(BrandingService::class)->current();
                $logoUrl = $branding->getFirstMediaUrl('logo') ?: null;

                return [
                    'app_name' => $branding->app_name ?: Branding::DEFAULTS['app_name'],
                    'primary_color' => $branding->primary_color ?: Branding::DEFAULTS['primary_color'],
                    'secondary_color' => $branding->secondary_color ?: Branding::DEFAULTS['secondary_color'],
                    'tertiary_color' => $branding->tertiary_color ?: Branding::DEFAULTS['tertiary_color'],
                    'logo_url' => $this->absoluteUrl($logoUrl),
                    'frontend_url' => $this->frontendUrl(),
                ];
            } catch (Throwable) {
                return $this->defaults();
            }
        });
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array{
     *     app_name: string,
     *     primary_color: string,
     *     secondary_color: string,
     *     tertiary_color: string,
     *     logo_url: string|null,
     *     frontend_url: string,
     * }
     */
    private function defaults(): array
    {
        return [
            'app_name' => Branding::DEFAULTS['app_name'],
            'primary_color' => Branding::DEFAULTS['primary_color'],
            'secondary_color' => Branding::DEFAULTS['secondary_color'],
            'tertiary_color' => Branding::DEFAULTS['tertiary_color'],
            'logo_url' => null,
            'frontend_url' => $this->frontendUrl(),
        ];
    }

    private function frontendUrl(): string
    {
        return rtrim((string) config('app.frontend_url', config('app.url')), '/');
    }

    private function absoluteUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
    }
}
