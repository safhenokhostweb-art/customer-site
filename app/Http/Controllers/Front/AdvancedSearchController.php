<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Helpers\CurrencyHelper;
use App\Helpers\SiteHelper;
use App\Models\Product;
use App\Models\Post;
use App\Models\Page;
use App\Models\ProductCategory;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * جستجوی پیشرفته — مشابه دیجی‌کالا، با پیشنهاد زنده، فیلتر و مرتب‌سازی.
 */
class AdvancedSearchController extends Controller
{
    private const SUGGEST_LIMIT = 10;
    private const RESULTS_PER_PAGE = 12;
    private const CACHE_TTL = 300; // 5 min

    public function index(Request $request)
    {
        if (!SiteHelper::moduleEnabled('advanced_search')) {
            abort(404);
        }
        if (!SiteHelper::shopEnabled() && !SiteHelper::blogEnabled()) {
            abort(404);
        }

        $q = trim((string) $request->q);
        $type = $request->input('type', 'all'); // all, products, posts
        $category = $request->input('category');
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $sort = $request->input('sort', 'relevance'); // relevance, price_asc, price_desc, newest

        $results = $this->search($q, $type, $category, $minPrice, $maxPrice, $sort, 1);
        $categories = $this->getFilterCategories($type);

        $pages = \App\Models\Page::published()->menu()->get();

        return view(SiteHelper::view('search.index'), compact(
            'q', 'type', 'category', 'minPrice', 'maxPrice', 'sort',
            'results', 'categories', 'pages'
        ));
    }

    /**
     * API پیشنهاد زنده (autocomplete) — با debounce از سمت کلاینت.
     */
    public function suggest(Request $request): JsonResponse
    {
        if (!SiteHelper::moduleEnabled('advanced_search')) {
            return response()->json(['items' => []], 403);
        }
        $q = trim((string) $request->q);
        if (mb_strlen($q) < 2) {
            return response()->json(['items' => []]);
        }

        $cacheKey = 'search_suggest_' . md5($q);
        $items = Cache::remember($cacheKey, 60, function () use ($q) {
            return $this->fetchSuggestions($q);
        });

        return response()->json(['items' => $items]);
    }

    /**
     * API نتایج جستجو (برای AJAX / infinite scroll).
     */
    public function results(Request $request): JsonResponse
    {
        if (!SiteHelper::moduleEnabled('advanced_search')) {
            return response()->json(['products' => [], 'posts' => [], 'has_more' => false, 'total' => 0], 403);
        }
        $q = trim((string) $request->q);
        $type = $request->input('type', 'all');
        $category = $request->input('category');
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $sort = $request->input('sort', 'relevance');
        $page = (int) $request->input('page', 1);

        $results = $this->search($q, $type, $category, $minPrice, $maxPrice, $sort, $page);

        return response()->json([
            'products' => $results['products'],
            'posts' => $results['posts'],
            'has_more' => $results['has_more'],
            'total' => $results['total'],
        ]);
    }

    private function fetchSuggestions(string $q): array
    {
        $items = [];
        $like = '%' . $q . '%';

        if (SiteHelper::shopEnabled() && $this->includeProducts()) {
            $products = Product::active()
                ->where(function ($qb) use ($like) {
                    $qb->where('title', 'like', $like)
                        ->orWhere('sku', 'like', $like);
                })
                ->orderBy('title')
                ->limit(self::SUGGEST_LIMIT)
                ->get(['id', 'title', 'slug', 'image', 'price', 'sale_price']);

            foreach ($products as $p) {
                $rawPrice = (float) ($p->sale_price ?? $p->price);
                $items[] = [
                    'type' => 'product',
                    'title' => $p->title,
                    'url' => route('shop.show', $p->slug),
                    'image' => $p->image ? \App\Helpers\ImageHelper::url($p->image) : null,
                    'price' => (int) round(CurrencyHelper::toDisplayValue($rawPrice)),
                ];
            }
        }

        if (SiteHelper::blogEnabled() && $this->includePosts() && count($items) < self::SUGGEST_LIMIT) {
            $posts = Post::published()
                ->where('title', 'like', $like)
                ->limit(self::SUGGEST_LIMIT - count($items))
                ->get(['id', 'title', 'slug', 'featured_image']);

            foreach ($posts as $p) {
                $items[] = [
                    'type' => 'post',
                    'title' => $p->title,
                    'url' => route('blog.show', $p->slug),
                    'image' => $p->featured_image ? \App\Helpers\ImageHelper::url($p->featured_image) : null,
                ];
            }
        }

        return array_slice($items, 0, self::SUGGEST_LIMIT);
    }

    private function search(
        string $q,
        string $type,
        ?string $category,
        ?string $minPrice,
        ?string $maxPrice,
        string $sort,
        int $page
    ): array {
        $products = collect();
        $posts = collect();
        $hasMore = false;
        $total = 0;

        $productQuery = null;
        $postQuery = null;

        if (SiteHelper::shopEnabled() && $this->includeProducts() && in_array($type, ['all', 'products'], true)) {
            $productQuery = Product::active()->with('category');
            if ($q) {
                $like = '%' . $q . '%';
                $productQuery->where(function ($qb) use ($like) {
                    $qb->where('title', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('sku', 'like', $like);
                });
            }
            if ($category) {
                $productQuery->whereHas('category', fn ($qb) => $qb->where('slug', $category));
            }
            $min = is_numeric($minPrice) ? CurrencyHelper::toStorageValue($minPrice) : null;
            $max = is_numeric($maxPrice) ? CurrencyHelper::toStorageValue($maxPrice) : null;
            if ($min !== null) {
                $productQuery->whereRaw('COALESCE(sale_price, price) >= ?', [$min]);
            }
            if ($max !== null) {
                $productQuery->whereRaw('COALESCE(sale_price, price) <= ?', [$max]);
            }
            switch ($sort) {
                case 'price_asc':
                    $productQuery->orderByRaw('COALESCE(sale_price, price) ASC');
                    break;
                case 'price_desc':
                    $productQuery->orderByRaw('COALESCE(sale_price, price) DESC');
                    break;
                case 'newest':
                    $productQuery->orderByDesc('created_at');
                    break;
                default:
                    $productQuery->orderBy('order')->orderByDesc('created_at');
            }
        }

        if (SiteHelper::blogEnabled() && $this->includePosts() && in_array($type, ['all', 'posts'], true)) {
            $postQuery = Post::published()->with('category');
            if ($q) {
                $like = '%' . $q . '%';
                $postQuery->where(function ($qb) use ($like) {
                    $qb->where('title', 'like', $like)
                        ->orWhere('body', 'like', $like)
                        ->orWhere('excerpt', 'like', $like);
                });
            }
            if ($category && $type === 'posts') {
                $postQuery->whereHas('category', fn ($qb) => $qb->where('slug', $category));
            }
            $postQuery->latest('published_at');
        }

        $perPage = $this->getResultsPerPage();
        $perType = (int) ceil($perPage / 2);
        if ($type === 'products') {
            $perType = $perPage;
        } elseif ($type === 'posts') {
            $perType = $perPage;
        }

        if ($productQuery) {
            $total += (clone $productQuery)->count();
            $products = $productQuery->skip(($page - 1) * $perType)->take($perType)->get();
        }
        if ($postQuery) {
            $total += (clone $postQuery)->count();
            $posts = $postQuery->skip(($page - 1) * $perType)->take($perType)->get();
        }

        $fetched = $products->count() + $posts->count();
        $hasMore = $fetched >= $perType && $fetched > 0;

        return [
            'products' => $products,
            'posts' => $posts,
            'has_more' => $hasMore,
            'total' => $total,
        ];
    }

    private function getFilterCategories(string $type): array
    {
        $out = [];
        if (SiteHelper::shopEnabled() && $this->includeProducts() && in_array($type, ['all', 'products'], true)) {
            $out['product'] = Cache::remember('search_cats_product', self::CACHE_TTL, fn () =>
                ProductCategory::orderBy('order')->get(['id', 'name', 'slug'])
            );
        }
        if (SiteHelper::blogEnabled() && $this->includePosts() && in_array($type, ['all', 'posts'], true)) {
            $out['post'] = Cache::remember('search_cats_post', self::CACHE_TTL, fn () =>
                Category::orderBy('order')->get(['id', 'name', 'slug'])
            );
        }
        return $out;
    }

    private function getResultsPerPage(): int
    {
        $raw = Setting::get('advanced_search_results_per_page', self::RESULTS_PER_PAGE);
        $value = (int) $raw;
        if ($value < 1) {
            $value = self::RESULTS_PER_PAGE;
        }
        return min(60, $value);
    }

    private function includeProducts(): bool
    {
        return $this->boolSetting('advanced_search_include_products', true);
    }

    private function includePosts(): bool
    {
        return $this->boolSetting('advanced_search_include_posts', true);
    }

    private function boolSetting(string $key, bool $default): bool
    {
        $raw = Setting::get($key, $default ? '1' : '0');
        if (is_bool($raw)) {
            return $raw;
        }
        if ($raw === null) {
            return $default;
        }
        $str = strtolower((string) $raw);
        return in_array($str, ['1', 'true', 'yes', 'on'], true);
    }
}
