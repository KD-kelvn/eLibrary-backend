<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Build an absolute public-disk URL using the current request host
 * (falls back to APP_URL). Avoids broken covers when APP_URL lacks the API port.
 */
final class PublicStorageUrl
{
    public static function make(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            return self::rewriteLegacyAbsoluteUrl($path);
        }

        $relative = Storage::disk('public')->url(ltrim($path, '/'));

        return self::absolute($relative);
    }

    protected static function absolute(string $relativePath): string
    {
        if (preg_match('#^https?://#i', $relativePath)) {
            return $relativePath;
        }

        $base = request()->getSchemeAndHttpHost()
            ?: rtrim((string) config('app.url'), '/');

        return rtrim($base, '/').'/'.ltrim($relativePath, '/');
    }

    /**
     * Older rows / misconfigured APP_URL may have stored full URLs pointing at
     * http://localhost/storage/... — rebuild from the storage path segment.
     */
    protected static function rewriteLegacyAbsoluteUrl(string $url): string
    {
        if (preg_match('#/storage/(.+)$#', $url, $match)) {
            return self::absolute('/storage/'.$match[1]);
        }

        return $url;
    }
}
