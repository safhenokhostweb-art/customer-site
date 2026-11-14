<?php

namespace App\Services\ExchangeRates;

use Closure;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Reads rates from any HTTPS JSON endpoint the site admin configures: one
 * dot-notation path per currency (e.g. `data.usd.sell`), in Toman or Rial.
 *
 * The URL is admin input, so the request is guarded against SSRF: HTTPS
 * only, no credentials in the URL, and every resolved address of the host
 * must be public (no loopback, private or reserved ranges). Redirects are
 * not followed.
 */
final class JsonEndpointExchangeRateProvider implements ExchangeRateProvider
{
    public const MAX_RATE_TOMAN = 1_000_000_000;

    /** @var Closure(string):list<string> */
    private Closure $resolver;

    /**
     * @param array<string, string> $paths code => dot path in the JSON body
     * @param (Closure(string):list<string>)|null $resolver host => IPs (tests inject one)
     */
    public function __construct(
        private readonly string $url,
        private readonly array $paths,
        private readonly string $unit = 'toman',
        ?Closure $resolver = null,
    ) {
        $this->resolver = $resolver ?? static fn (string $host): array => gethostbynamel($host) ?: [];
    }

    public function fetch(array $codes): array
    {
        $this->assertSafeUrl($this->url);

        try {
            $response = Http::timeout(8)->connectTimeout(5)->withoutRedirecting()->acceptJson()->get($this->url);
        } catch (Throwable) {
            throw new ExchangeRateException('اتصال به سرویس نرخ ارز برقرار نشد.');
        }
        if (!$response->successful()) {
            throw new ExchangeRateException('سرویس نرخ ارز پاسخ معتبر نداد (کد ' . $response->status() . ').');
        }
        $json = $response->json();
        if (!is_array($json)) {
            throw new ExchangeRateException('پاسخ سرویس نرخ ارز JSON معتبر نیست.');
        }

        $rates = [];
        foreach ($codes as $code) {
            $path = trim((string) ($this->paths[$code] ?? ''));
            if ($path === '') {
                continue;
            }
            $value = self::toNumber(data_get($json, $path));
            if ($value === null) {
                continue;
            }
            $toman = $this->unit === 'rial' ? $value / 10 : $value;
            if ($toman > 0 && $toman < self::MAX_RATE_TOMAN) {
                $rates[$code] = round($toman, 4);
            }
        }

        return $rates;
    }

    private function assertSafeUrl(string $url): void
    {
        $parts = parse_url($url);
        if (!is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            throw new ExchangeRateException('آدرس سرویس نرخ ارز باید با https شروع شود.');
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new ExchangeRateException('آدرس سرویس نرخ ارز نباید نام کاربری یا رمز داشته باشد.');
        }
        if (isset($parts['port']) && (int) $parts['port'] !== 443) {
            throw new ExchangeRateException('فقط درگاه استاندارد https مجاز است.');
        }

        $host = trim($parts['host'], '[]');
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : ($this->resolver)($host);
        if ($ips === []) {
            throw new ExchangeRateException('نام میزبان سرویس نرخ ارز پیدا نشد.');
        }
        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new ExchangeRateException('آدرس سرویس نرخ ارز به شبکهٔ داخلی اشاره می‌کند و مجاز نیست.');
            }
        }
    }

    private static function toNumber(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        if (!is_string($value)) {
            return null;
        }
        $clean = str_replace([',', '٬', ' '], '', \SaaSPlatform\ThemeRuntime\Support\PersianNumber::latinDigits($value));
        $clean = str_replace('٫', '.', $clean);

        return is_numeric($clean) ? (float) $clean : null;
    }
}
