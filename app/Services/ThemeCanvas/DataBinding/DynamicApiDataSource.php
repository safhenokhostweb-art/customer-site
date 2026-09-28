<?php

namespace App\Services\ThemeCanvas\DataBinding;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use SaaSPlatform\ThemeRuntime\DataBinding\DataSourceConfig;

/** Controlled HTTP adapter for dynamic feed widgets. */
final class DynamicApiDataSource
{
    /** @return list<array<string,mixed>> */
    public function fetch(DataSourceConfig $source): array
    {
        $host = strtolower((string) parse_url($source->endpointUrl, PHP_URL_HOST));
        $allowedHosts = array_values(array_filter(array_map(
            static fn (mixed $value): string => strtolower(trim((string) $value)),
            (array) config('theme-runtime.dynamic_api_hosts', []),
        )));
        if ($host === '' || !in_array($host, $allowedHosts, true)) {
            throw new InvalidArgumentException('Dynamic API host is not allowlisted.');
        }

        $response = Http::acceptJson()
            ->connectTimeout(2)
            ->timeout(5)
            ->withOptions(['allow_redirects' => false])
            ->get($source->endpointUrl, $source->queryParams)
            ->throw();

        if ((int) $response->header('Content-Length', 0) > 1_000_000) {
            throw new InvalidArgumentException('Dynamic API response is too large.');
        }
        $payload = $response->json();
        if (is_array($payload) && !array_is_list($payload)) {
            $payload = $payload['items'] ?? $payload['data'] ?? null;
        }
        if (!is_array($payload) || !array_is_list($payload)) {
            throw new InvalidArgumentException('Dynamic API response must contain an item list.');
        }

        return array_values(array_filter(array_slice($payload, 0, 50), 'is_array'));
    }
}
