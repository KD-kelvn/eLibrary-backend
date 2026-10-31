<?php

namespace Modules\Integrations\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Modules\Integrations\Exceptions\IntegrationsException;

/**
 * Outbound HTTP client for university core pull (FR-SYNC-02).
 *
 * Expected: GET {base}{path}?updated_since=ISO&page=n → { data: [], next_page: int|null }
 */
class UniversityCoreClient
{
    public function __construct(private readonly UniversityProviderConfig $config) {}

    /**
     * @return array{data: list<array<string, mixed>>, next_page: int|null}
     */
    public function fetchStudentsPage(?string $updatedSince, int $page = 1): array
    {
        $cfg = $this->config->pullConfig();
        $base = rtrim((string) ($cfg['base_url'] ?? ''), '/');
        if ($base === '') {
            throw IntegrationsException::unprocessable('University core base_url is not configured.');
        }

        $path = (string) ($cfg['students_path'] ?? '/students');
        if ($path === '' || ! str_starts_with($path, '/')) {
            $path = '/'.ltrim($path, '/');
        }

        $query = ['page' => $page];
        if (filled($updatedSince)) {
            $query['updated_since'] = $updatedSince;
        }

        $response = $this->http($cfg)
            ->get($base.$path, $query);

        if (! $response->successful()) {
            throw IntegrationsException::unprocessable(
                'University core pull failed: HTTP '.$response->status(),
            );
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw IntegrationsException::unprocessable('University core returned invalid JSON.');
        }

        $data = $json['data'] ?? null;
        if (! is_array($data)) {
            throw IntegrationsException::unprocessable('University core response missing data[].');
        }

        $next = $json['next_page'] ?? null;
        $nextPage = is_numeric($next) ? (int) $next : null;

        /** @var list<array<string, mixed>> $rows */
        $rows = array_values(array_filter($data, 'is_array'));

        return [
            'data' => $rows,
            'next_page' => $nextPage,
        ];
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function http(array $cfg): PendingRequest
    {
        $request = Http::acceptJson()
            ->timeout((int) ($cfg['timeout'] ?? 30))
            ->retry(2, 200);

        $apiKey = (string) ($cfg['api_key'] ?? '');
        $scheme = (string) ($cfg['auth_scheme'] ?? 'bearer');

        if ($apiKey !== '') {
            if ($scheme === 'header') {
                $header = (string) ($cfg['api_key_header'] ?? 'X-Api-Key');
                $request = $request->withHeaders([$header => $apiKey]);
            } else {
                $request = $request->withToken($apiKey);
            }
        }

        return $request;
    }
}
