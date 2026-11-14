<?php

namespace Tests\Unit\ExchangeRates;

use App\Services\ExchangeRates\ExchangeRateException;
use App\Services\ExchangeRates\JsonEndpointExchangeRateProvider;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class JsonEndpointExchangeRateProviderTest extends TestCase
{
    private function provider(string $url, array $paths = ['USD' => 'usd'], string $unit = 'toman', array $ips = ['93.184.216.34']): JsonEndpointExchangeRateProvider
    {
        return new JsonEndpointExchangeRateProvider($url, $paths, $unit, fn (string $host) => $ips);
    }

    public static function unsafeUrls(): array
    {
        return [
            'plain http' => ['http://rates.example.com/x', ['93.184.216.34']],
            'credentials' => ['https://user:pass@rates.example.com/x', ['93.184.216.34']],
            'loopback ip' => ['https://127.0.0.1/x', []],
            'private ip' => ['https://10.0.0.5/x', []],
            'host resolving to private range' => ['https://rates.internal.example/x', ['192.168.1.10']],
            'host resolving to metadata ip' => ['https://meta.example/x', ['169.254.169.254']],
            'non-standard port' => ['https://rates.example.com:8443/x', ['93.184.216.34']],
            'unresolvable host' => ['https://nowhere.example/x', []],
        ];
    }

    #[DataProvider('unsafeUrls')]
    public function test_refuses_unsafe_destinations_without_any_request(string $url, array $ips): void
    {
        Http::fake();

        try {
            $this->provider($url, ips: $ips)->fetch(['USD']);
            $this->fail("{$url} was allowed");
        } catch (ExchangeRateException) {
            Http::assertNothingSent();
        }
    }

    public function test_reads_paths_converts_rial_and_skips_invalid_values(): void
    {
        Http::fake(['*' => Http::response(['usd' => ['sell' => '۱٬۰۲۰٬۰۰۰'], 'eur' => 'n/a', 'aed' => -5, 'cny' => 140000])]);

        $rates = $this->provider('https://rates.example.com/x', ['USD' => 'usd.sell', 'EUR' => 'eur', 'AED' => 'aed', 'CNY' => 'cny'], 'rial')
            ->fetch(['USD', 'EUR', 'AED', 'CNY']);

        $this->assertSame(['USD' => 102000.0, 'CNY' => 14000.0], $rates);
    }

    public function test_http_failure_is_reported(): void
    {
        Http::fake(['*' => Http::response('down', 503)]);

        $this->expectException(ExchangeRateException::class);
        $this->provider('https://rates.example.com/x')->fetch(['USD']);
    }
}
