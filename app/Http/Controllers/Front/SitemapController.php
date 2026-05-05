<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Helpers\SiteHelper;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public const SITEMAP_CACHE_KEY = 'sitemap_xml';

    /**
     * خروجی sitemap.xml برای موتورهای جستجو.
     */
    public function index(): Response
    {
        $ttl = config('seo.sitemap_cache_ttl', 3600);
        $xml = Cache::remember(self::SITEMAP_CACHE_KEY, $ttl, function () {
            return $this->buildSitemapXml();
        });

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
        $ttl = config('seo.sitemap_cache_ttl', 3600);
        $xml = Cache::remember(self::SITEMAP_CACHE_KEY . '_part_' . $index, $ttl, function () use ($index) {
            $entries = $this->collectEntries();
            $maxPerFile = config('seo.sitemap_max_urls_per_file', 50000);
            $chunks = array_chunk($entries, $maxPerFile);
            $chunk = $chunks[$index] ?? [];
            return $this->buildXml($chunk);
        });
        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * پاک کردن کش sitemap (برای فراخوانی بعد از تغییر محتوا).
     */
    public static function clearCache(): void
    {
        Cache::forget(self::SITEMAP_CACHE_KEY);
        for ($i = 0; $i < 10; $i++) {
            Cache::forget(self::SITEMAP_CACHE_KEY . '_part_' . $i);
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
