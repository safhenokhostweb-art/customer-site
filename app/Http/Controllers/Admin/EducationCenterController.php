<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Education Center for customer-site.
 * Fetches eligible articles from saas-platform (Rule 1 — permission-aware sync).
 * Displays update badge on all articles (Rule 10).
 */
class EducationCenterController extends Controller
{
    private const CACHE_TTL = 1800; // 30 minutes

    public function index(Request $request)
    {
        $articles      = $this->fetchEligibleArticles();
        $walkthroughs  = $this->fetchWalkthroughs();
        $learningPaths = $this->fetchLearningPaths();
        $module        = $request->query('module');
        $search        = $request->query('q');

        // Filter by module
        if ($module) {
            $articles = array_filter($articles, fn($a) => ($a['module_slug'] ?? '') === $module);
        }

        // Search
        if ($search) {
            $s = mb_strtolower($search);
            $articles = array_filter($articles, function ($a) use ($s) {
                return str_contains(mb_strtolower($a['title'] ?? ''), $s)
                    || str_contains(mb_strtolower($a['excerpt'] ?? ''), $s);
            });
        }

        // Group by module for navigation
        $byModule = [];
        foreach ($this->fetchEligibleArticles() as $a) {
            $mod = $a['module_slug'] ?? 'other';
            $byModule[$mod][] = $a;
        }

        return view('admin.education-center.index', [
            'articles'      => array_values($articles),
            'walkthroughs'  => $walkthroughs,
            'learningPaths' => $learningPaths,
            'byModule'      => $byModule,
            'activeModule'  => $module,
            'search'        => $search,
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $articles = $this->fetchEligibleArticles();
        $article  = null;

        foreach ($articles as $a) {
            if (($a['slug'] ?? '') === $slug) {
                $article = $a;
                break;
            }
        }

        if (! $article) {
            abort(404, 'مستند یافت نشد یا دسترسی به آن مجاز نیست.');
        }

        return view('admin.education-center.show', [
            'article' => $article,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Platform API calls
    // ──────────────────────────────────────────────────────────────────────────

    private function fetchEligibleArticles(): array
    {
        $siteId      = $this->resolvePlatformSiteId();
        $platformUrl = $this->platformUrl();

        if (! $platformUrl || ! $siteId) {
            return [];
        }

        $cacheKey = "edu_center_articles_{$siteId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($platformUrl, $siteId) {
            try {
                $response = Http::withToken($this->platformToken())
                    ->timeout(10)
                    ->get("{$platformUrl}/api/v1/education/sync/{$siteId}/articles");

                if ($response->successful()) {
                    return $response->json('articles', []);
                }
            } catch (\Throwable) {
                // Silently fail — show empty state
            }
            return [];
        });
    }

    private function fetchWalkthroughs(): array
    {
        $siteId      = $this->resolvePlatformSiteId();
        $platformUrl = $this->platformUrl();

        if (! $platformUrl || ! $siteId) {
            return [];
        }

        $cacheKey = "edu_center_walkthroughs_{$siteId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($platformUrl, $siteId) {
            try {
                $response = Http::withToken($this->platformToken())
                    ->timeout(10)
                    ->get("{$platformUrl}/api/v1/education/sync/{$siteId}/walkthroughs");

                if ($response->successful()) {
                    return $response->json('walkthroughs', []);
                }
            } catch (\Throwable) {
            }
            return [];
        });
    }

    private function fetchLearningPaths(): array
    {
        $siteId      = $this->resolvePlatformSiteId();
        $platformUrl = $this->platformUrl();

        if (! $platformUrl || ! $siteId) {
            return [];
        }

        $cacheKey = "edu_center_paths_{$siteId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($platformUrl, $siteId) {
            try {
                $response = Http::withToken($this->platformToken())
                    ->timeout(10)
                    ->get("{$platformUrl}/api/v1/education/sync/{$siteId}/learning-paths");

                if ($response->successful()) {
                    return $response->json('learning_paths', []);
                }
            } catch (\Throwable) {
            }
            return [];
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Config helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function platformUrl(): ?string
    {
        return rtrim((string) config('site.platform_url', ''), '/') ?: null;
    }

    private function resolvePlatformSiteId(): ?int
    {
        $explicit = config('site.platform_site_id');
        return $explicit ? (int) $explicit : null;
    }

    private function platformToken(): string
    {
        return (string) config('site.platform_api_token', '');
    }
}
