<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Form;
use App\Models\License;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Helpers\SiteHelper;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SitemapController extends Controller
{
    public const SITEMAP_CACHE_KEY = 'sitemap_xml';

    /**
     * Index of the hosts that currently hold a cached sitemap.
     *
     * A global flush has to find every host-scoped key it created. With the
     * database driver a LIKE against the `cache` table would tie this to one
     * store, and Cache::flush() would destroy unrelated application cache, so
     * the controller tracks its own keys here instead. Refreshed on every
     * cached request, so it stays warmer than the entries it points at.
     */
    private const HOST_INDEX_KEY = 'sitemap_xml_hosts';

    private const HOST_INDEX_TTL = 604800; // 7 days

    /** Highest paginated index flushed by clearCache(); preserves the
     *  pre-existing 0..9 window of the original clearCache(). */
    private const MAX_PART_INDEX = 9;

    /**
     * Upper bound for the host segment of a cache key.
     *
     * `cache`.`key` is a VARCHAR(255) PRIMARY KEY and config('cache.prefix')
     * is prepended to it, so an untruncated host could push the key past the
     * column limit. 200 leaves headroom for the prefix and the `_part_N`
     * suffix. DNS hostnames never approach this length in practice, so the
     * truncation is a safety bound rather than a real collision surface.
     */
    private const MAX_KEY_LENGTH = 200;

    /**
     * Normalize any host-ish value into the segment used inside cache keys.
     *
     * Reuses the platform's own License::normalizeDomain() — the exact same
     * comparison the license/domain binding in License::current() and
     * License::isValid() uses — so "Example.COM", "https://example.com/" and
     * "example.com." all resolve to one key instead of three.
     */
    public static function normalizeCacheHost(string $host): string
    {
        $host = rtrim(License::normalizeDomain($host), '.');

        return substr($host, 0, self::MAX_KEY_LENGTH);
    }

    private function cacheHost(): string
    {
        return self::normalizeCacheHost((string) request()->getHost());
    }

    /**
     * Whether this request's host is allowed to WRITE a sitemap cache entry.
     *
     * bootstrap/app.php registers trustProxies(at: '*'), so request()->getHost()
     * honors X-Forwarded-Host — a client-controlled header. Caching on it
     * unfiltered would let any caller mint an arbitrary number of permanent
     * `cache` rows (unbounded growth / cache exhaustion) and pre-empt the key
     * a real tenant will later use.
     *
     * So a write only happens for a host that is genuinely this
     * installation's own: a `licenses`.`domain` row, or the owner site's
     * configured app.url. Anything else — an unknown vhost, a spoofed
     * forwarded host, or a console/queue context with no request host — is
     * built fresh on every request and never persisted.
     */
    private function isCacheableHost(string $host): bool
    {
        if ($host === '') {
            return false;
        }

        if ($host === self::normalizeCacheHost((string) config('app.url'))) {
            return true;
        }

        if (! Schema::hasTable('licenses')) {
            return false;
        }

        return License::query()->pluck('domain')
            ->contains(fn ($domain) => self::normalizeCacheHost((string) $domain) === $host);
    }

    /** Record that $host currently holds a cached sitemap. */
    private function rememberHost(string $host): void
    {
        $index = Cache::get(self::HOST_INDEX_KEY, []);
        $index = is_array($index) ? $index : [];

        if (! in_array($host, $index, true)) {
            $index[] = $host;
        }

        Cache::put(self::HOST_INDEX_KEY, array_values($index), self::HOST_INDEX_TTL);
    }

    /**
     * خروجی sitemap.xml برای موتورهای جستجو.
     */
    public function index(): Response
    {
        $ttl  = config('seo.sitemap_cache_ttl', 3600);
        $host = $this->cacheHost();

        if (! $this->isCacheableHost($host)) {
            $xml = $this->buildSitemapXml();
        } else {
            $xml = Cache::remember(self::SITEMAP_CACHE_KEY . '_' . $host, $ttl, function () {
                return $this->buildSitemapXml();
            });
            $this->rememberHost($host);
        }

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    private function buildSitemapXml(): string
    {
        $entries = $this->collectEntries();
        $maxPerFile = config('seo.sitemap_max_urls_per_file', 50000);
        if (count($entries) > $maxPerFile) {
            return $this->buildSitemapIndex($entries, $maxPerFile);
        }
        return $this->buildXml($entries);
    }

    private function collectEntries(): array
    {
        $base = url('/');
        $entries = [];

        $entries[] = [
            'loc' => $base,
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        foreach (Page::published()->get() as $page) {
            $entries[] = [
                'loc' => route('page.show', $page->slug),
                'lastmod' => $page->updated_at?->toW3cString(),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ];
        }

        if (SiteHelper::blogEnabled()) {
            $entries[] = [
                'loc' => route('blog.index'),
                'changefreq' => 'daily',
                'priority' => '0.9',
            ];
            foreach (Category::orderBy('order')->get() as $cat) {
                $entries[] = [
                    'loc' => route('blog.category', $cat->slug),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ];
            }
            foreach (Post::published()->get() as $post) {
                $entries[] = [
                    'loc' => route('blog.show', $post->slug),
                    'lastmod' => ($post->updated_at ?? $post->published_at)?->toW3cString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.7',
                ];
            }
        }

        if (SiteHelper::moduleEnabled('forms')) {
            $entries[] = [
                'loc' => route('forms.index'),
                'changefreq' => 'weekly',
                'priority' => '0.65',
            ];
            foreach (Form::query()->where('is_active', true)->orderBy('title')->get() as $form) {
                $entries[] = [
                    'loc' => route('forms.show', $form->slug),
                    'lastmod' => $form->updated_at?->toW3cString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.55',
                ];
            }
        }

        if (SiteHelper::shopEnabled()) {
            $entries[] = [
                'loc' => route('shop.index'),
                'changefreq' => 'daily',
                'priority' => '0.9',
            ];
            foreach (Product::active()->orderBy('order')->get() as $product) {
                $entries[] = [
                    'loc' => route('shop.show', $product->slug),
                    'lastmod' => $product->updated_at?->toW3cString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ];
            }
        }

        return $entries;
    }

    private function buildSitemapIndex(array $entries, int $maxPerFile): string
    {
        $chunks = array_chunk($entries, $maxPerFile);
        $base = url('/');
        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $out .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($chunks as $i => $chunk) {
            $out .= '  <sitemap><loc>' . htmlspecialchars($base . '/sitemap-part-' . $i . '.xml', ENT_XML1, 'UTF-8') . '</loc></sitemap>' . "\n";
        }
        $out .= '</sitemapindex>';
        return $out;
    }

    /**
     * خروجی sitemap بخشی (برای سایت‌های بزرگ).
     */
    public function part(int $index): Response
    {
        $ttl  = config('seo.sitemap_cache_ttl', 3600);
        $host = $this->cacheHost();

        if (! $this->isCacheableHost($host)) {
            $entries = $this->collectEntries();
            $maxPerFile = config('seo.sitemap_max_urls_per_file', 50000);
            $chunk = array_chunk($entries, $maxPerFile)[$index] ?? [];
            $xml = $this->buildXml($chunk);
        } else {
            $xml = Cache::remember(self::SITEMAP_CACHE_KEY . '_' . $host . '_part_' . $index, $ttl, function () use ($index) {
                $entries = $this->collectEntries();
                $maxPerFile = config('seo.sitemap_max_urls_per_file', 50000);
                $chunks = array_chunk($entries, $maxPerFile);
                $chunk = $chunks[$index] ?? [];
                return $this->buildXml($chunk);
            });
            $this->rememberHost($host);
        }

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * پاک کردن کش sitemap (برای فراخوانی بعد از تغییر محتوا).
     *
     * Every entry is host-scoped, so a bare call flushes the hosts this
     * controller has actually cached (tracked in HOST_INDEX_KEY) rather than
     * guessing at hostnames. Passing $host narrows it to that one tenant,
     * which is what a caller that genuinely knows its own domain should use.
     */
    public static function clearCache(?string $host = null): void
    {
        if ($host !== null && trim($host) !== '') {
            self::clearHostCache(self::normalizeCacheHost($host));

            return;
        }

        $index = Cache::get(self::HOST_INDEX_KEY, []);
        foreach (is_array($index) ? $index : [] as $indexed) {
            self::clearHostCache(self::normalizeCacheHost((string) $indexed));
        }

        Cache::forget(self::HOST_INDEX_KEY);
    }

    private static function clearHostCache(string $host): void
    {
        for ($i = 0; $i <= self::MAX_PART_INDEX; $i++) {
            Cache::forget(self::SITEMAP_CACHE_KEY . '_' . $host . '_part_' . $i);
        }
        Cache::forget(self::SITEMAP_CACHE_KEY . '_' . $host);

        $index = Cache::get(self::HOST_INDEX_KEY, []);
        $index = is_array($index) ? $index : [];
        $remaining = array_values(array_filter(
            $index,
            fn ($indexed) => (string) $indexed !== $host
        ));

        if ($remaining === []) {
            Cache::forget(self::HOST_INDEX_KEY);
        } else {
            Cache::put(self::HOST_INDEX_KEY, $remaining, self::HOST_INDEX_TTL);
        }
    }

    private function buildXml(array $entries): string
    {
        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($entries as $e) {
            $out .= '  <url>' . "\n";
            $out .= '    <loc>' . htmlspecialchars($e['loc'], ENT_XML1, 'UTF-8') . '</loc>' . "\n";
            if (! empty($e['lastmod'])) {
                $out .= '    <lastmod>' . htmlspecialchars($e['lastmod'], ENT_XML1, 'UTF-8') . '</lastmod>' . "\n";
            }
            if (! empty($e['changefreq'])) {
                $out .= '    <changefreq>' . htmlspecialchars($e['changefreq'], ENT_XML1, 'UTF-8') . '</changefreq>' . "\n";
            }
            if (isset($e['priority'])) {
                $out .= '    <priority>' . htmlspecialchars((string) $e['priority'], ENT_XML1, 'UTF-8') . '</priority>' . "\n";
            }
            $out .= '  </url>' . "\n";
        }
        $out .= '</urlset>';
        return $out;
    }
}
