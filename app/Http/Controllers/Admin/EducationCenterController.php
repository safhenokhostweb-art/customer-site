<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Education Center for customer-site admin panel.
 * Read: fetches eligible content from saas-platform via sync API.
 * Write: proxies CRUD operations to saas-platform site-admin API.
 */
class EducationCenterController extends Controller
{
    private const CACHE_TTL = 1800; // 30 minutes

    // ──────────────────────────────────────────────────────────────────────────
    // READ — public education center (existing behaviour)
    // ──────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $articles      = $this->fetchEligibleArticles();
        $walkthroughs  = $this->fetchWalkthroughs();
        $learningPaths = $this->fetchLearningPaths();
        $module        = $request->query('module');
        $search        = $request->query('q');

        if ($module) {
            $articles = array_filter($articles, fn($a) => ($a['module_slug'] ?? '') === $module);
        }

        if ($search) {
            $s = mb_strtolower($search);
            $articles = array_filter($articles, function ($a) use ($s) {
                return str_contains(mb_strtolower($a['title'] ?? ''), $s)
                    || str_contains(mb_strtolower($a['excerpt'] ?? ''), $s);
            });
        }

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
    // MANAGE — full content management (all statuses)
    // ──────────────────────────────────────────────────────────────────────────

    public function manage(Request $request)
    {
        $tab    = $request->query('tab', 'articles');
        $status = $request->query('status');
        $q      = $request->query('q');
        $siteId = $this->resolvePlatformSiteId();

        $articles      = [];
        $walkthroughs  = [];
        $learningPaths = [];
        $meta          = [];

        if ($tab === 'articles' || $tab === 'all') {
            $params = array_filter(['status' => $status, 'q' => $q, 'per_page' => 50]);
            $res = $this->platformAdminGet("articles", $siteId, $params);
            $articles = $res['articles'] ?? [];
            $meta     = ['total' => $res['total'] ?? count($articles)];
        }

        if ($tab === 'walkthroughs') {
            $res = $this->platformAdminGet("walkthroughs", $siteId, ['per_page' => 50]);
            $walkthroughs = $res['walkthroughs'] ?? [];
        }

        if ($tab === 'learning-paths') {
            $res = $this->platformAdminGet("learning-paths", $siteId, ['per_page' => 50]);
            $learningPaths = $res['learning_paths'] ?? [];
        }

        return view('admin.education-center.manage', compact(
            'articles', 'walkthroughs', 'learningPaths', 'tab', 'status', 'q', 'meta'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // ARTICLE CRUD
    // ──────────────────────────────────────────────────────────────────────────

    public function createArticle()
    {
        return view('admin.education-center.article-form', ['article' => null]);
    }

    public function storeArticle(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'body'         => 'required|string',
            'excerpt'      => 'nullable|string|max:500',
            'module_slug'  => 'required|string|max:100',
            'content_type' => 'nullable|in:article,guide,tutorial,faq',
            'status'       => 'nullable|in:draft,published',
        ]);

        $siteId = $this->resolvePlatformSiteId();
        $res    = $this->platformAdminPost("articles", $siteId, $data);

        if (isset($res['article'])) {
            $this->clearArticleCache($siteId);
            return redirect()->route('admin.education-center.manage')
                ->with('success', 'مقاله با موفقیت ایجاد شد.');
        }

        return back()->withErrors(['api' => 'خطا در ذخیره مقاله. لطفاً دوباره تلاش کنید.']);
    }

    public function editArticle(int $id)
    {
        $siteId  = $this->resolvePlatformSiteId();
        $res     = $this->platformAdminGet("articles", $siteId, ['per_page' => 200]);
        $article = collect($res['articles'] ?? [])->firstWhere('id', $id);

        if (! $article) {
            abort(404, 'مقاله یافت نشد.');
        }

        return view('admin.education-center.article-form', ['article' => $article]);
    }

    public function updateArticle(Request $request, int $id)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'body'         => 'required|string',
            'excerpt'      => 'nullable|string|max:500',
            'module_slug'  => 'required|string|max:100',
            'content_type' => 'nullable|in:article,guide,tutorial,faq',
            'status'       => 'nullable|in:draft,published,archived',
        ]);

        $siteId = $this->resolvePlatformSiteId();
        $res    = $this->platformAdminPut("articles/{$id}", $siteId, $data);

        if (isset($res['article'])) {
            $this->clearArticleCache($siteId);
            return redirect()->route('admin.education-center.manage')
                ->with('success', 'مقاله با موفقیت بروزرسانی شد.');
        }

        return back()->withErrors(['api' => 'خطا در بروزرسانی مقاله.']);
    }

    public function destroyArticle(int $id)
    {
        $siteId = $this->resolvePlatformSiteId();
        $this->platformAdminDelete("articles/{$id}", $siteId);
        $this->clearArticleCache($siteId);

        return redirect()->route('admin.education-center.manage')
            ->with('success', 'مقاله حذف شد.');
    }

    public function publishArticle(int $id)
    {
        $siteId = $this->resolvePlatformSiteId();
        $this->platformAdminPost("articles/{$id}/publish", $siteId, []);
        $this->clearArticleCache($siteId);

        return redirect()->route('admin.education-center.manage')
            ->with('success', 'مقاله منتشر شد.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // WALKTHROUGH CRUD
    // ──────────────────────────────────────────────────────────────────────────

    public function createWalkthrough()
    {
        return view('admin.education-center.walkthrough-form', ['walkthrough' => null]);
    }

    public function storeWalkthrough(Request $request)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'module_slug'   => 'required|string|max:100',
            'trigger_route' => 'nullable|string|max:200',
            'is_active'     => 'boolean',
            'steps'         => 'nullable|array',
            'steps.*.title' => 'required_with:steps|string|max:255',
            'steps.*.body'  => 'required_with:steps|string',
        ]);

        // Number steps automatically
        if (! empty($data['steps'])) {
            foreach ($data['steps'] as $i => $step) {
                $data['steps'][$i]['step_number'] = $i + 1;
            }
        }

        $siteId = $this->resolvePlatformSiteId();
        $res    = $this->platformAdminPost("walkthroughs", $siteId, $data);

        if (isset($res['walkthrough'])) {
            return redirect()->route('admin.education-center.manage', ['tab' => 'walkthroughs'])
                ->with('success', 'راهنما با موفقیت ایجاد شد.');
        }

        return back()->withErrors(['api' => 'خطا در ذخیره راهنما.']);
    }

    public function editWalkthrough(int $id)
    {
        $siteId      = $this->resolvePlatformSiteId();
        $res         = $this->platformAdminGet("walkthroughs", $siteId, ['per_page' => 200]);
        $walkthrough = collect($res['walkthroughs'] ?? [])->firstWhere('id', $id);

        if (! $walkthrough) {
            abort(404, 'راهنما یافت نشد.');
        }

        return view('admin.education-center.walkthrough-form', ['walkthrough' => $walkthrough]);
    }

    public function updateWalkthrough(Request $request, int $id)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'module_slug'   => 'required|string|max:100',
            'trigger_route' => 'nullable|string|max:200',
            'is_active'     => 'boolean',
        ]);

        $siteId = $this->resolvePlatformSiteId();
        $res    = $this->platformAdminPut("walkthroughs/{$id}", $siteId, $data);

        if (isset($res['walkthrough'])) {
            return redirect()->route('admin.education-center.manage', ['tab' => 'walkthroughs'])
                ->with('success', 'راهنما بروزرسانی شد.');
        }

        return back()->withErrors(['api' => 'خطا در بروزرسانی راهنما.']);
    }

    public function destroyWalkthrough(int $id)
    {
        $siteId = $this->resolvePlatformSiteId();
        $this->platformAdminDelete("walkthroughs/{$id}", $siteId);

        return redirect()->route('admin.education-center.manage', ['tab' => 'walkthroughs'])
            ->with('success', 'راهنما حذف شد.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LEARNING PATH CRUD
    // ──────────────────────────────────────────────────────────────────────────

    public function createLearningPath()
    {
        return view('admin.education-center.learning-path-form', ['path' => null]);
    }

    public function storeLearningPath(Request $request)
    {
        $data = $request->validate([
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'path_type'         => 'nullable|in:onboarding,feature,role,custom',
            'estimated_minutes' => 'nullable|integer|min:1',
            'is_active'         => 'boolean',
        ]);

        $siteId = $this->resolvePlatformSiteId();
        $res    = $this->platformAdminPost("learning-paths", $siteId, $data);

        if (isset($res['learning_path'])) {
            return redirect()->route('admin.education-center.manage', ['tab' => 'learning-paths'])
                ->with('success', 'مسیر یادگیری ایجاد شد.');
        }

        return back()->withErrors(['api' => 'خطا در ذخیره مسیر یادگیری.']);
    }

    public function editLearningPath(int $id)
    {
        $siteId = $this->resolvePlatformSiteId();
        $res    = $this->platformAdminGet("learning-paths", $siteId, ['per_page' => 200]);
        $path   = collect($res['learning_paths'] ?? [])->firstWhere('id', $id);

        if (! $path) {
            abort(404, 'مسیر یادگیری یافت نشد.');
        }

        return view('admin.education-center.learning-path-form', ['path' => $path]);
    }

    public function updateLearningPath(Request $request, int $id)
    {
        $data = $request->validate([
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'path_type'         => 'nullable|in:onboarding,feature,role,custom',
            'estimated_minutes' => 'nullable|integer|min:1',
            'is_active'         => 'boolean',
        ]);

        $siteId = $this->resolvePlatformSiteId();
        $res    = $this->platformAdminPut("learning-paths/{$id}", $siteId, $data);

        if (isset($res['learning_path'])) {
            return redirect()->route('admin.education-center.manage', ['tab' => 'learning-paths'])
                ->with('success', 'مسیر یادگیری بروزرسانی شد.');
        }

        return back()->withErrors(['api' => 'خطا در بروزرسانی مسیر یادگیری.']);
    }

    public function destroyLearningPath(int $id)
    {
        $siteId = $this->resolvePlatformSiteId();
        $this->platformAdminDelete("learning-paths/{$id}", $siteId);

        return redirect()->route('admin.education-center.manage', ['tab' => 'learning-paths'])
            ->with('success', 'مسیر یادگیری حذف شد.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Platform API helpers — SYNC (read)
    // ──────────────────────────────────────────────────────────────────────────

    private function fetchEligibleArticles(): array
    {
        $siteId      = $this->resolvePlatformSiteId();
        $platformUrl = $this->platformUrl();

        if (! $platformUrl || ! $siteId) {
            return [];
        }

        return Cache::remember("edu_center_articles_{$siteId}", self::CACHE_TTL, function () use ($platformUrl, $siteId) {
            try {
                $response = Http::withToken($this->platformToken())
                    ->timeout(10)
                    ->get("{$platformUrl}/api/v1/education/sync/{$siteId}/articles");

                if ($response->successful()) {
                    return $response->json('articles', []);
                }
            } catch (\Throwable) {
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

        return Cache::remember("edu_center_walkthroughs_{$siteId}", self::CACHE_TTL, function () use ($platformUrl, $siteId) {
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

        return Cache::remember("edu_center_paths_{$siteId}", self::CACHE_TTL, function () use ($platformUrl, $siteId) {
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
    // Platform API helpers — ADMIN (write)
    // ──────────────────────────────────────────────────────────────────────────

    private function platformAdminGet(string $resource, ?int $siteId, array $params = []): array
    {
        if (! $siteId || ! $this->platformUrl()) {
            return [];
        }

        try {
            $response = Http::withToken($this->platformToken())
                ->timeout(15)
                ->get("{$this->platformUrl()}/api/v1/education/site-admin/{$siteId}/{$resource}", $params);

            return $response->successful() ? $response->json() ?? [] : [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function platformAdminPost(string $resource, ?int $siteId, array $data): array
    {
        if (! $siteId || ! $this->platformUrl()) {
            return [];
        }

        try {
            $response = Http::withToken($this->platformToken())
                ->timeout(15)
                ->post("{$this->platformUrl()}/api/v1/education/site-admin/{$siteId}/{$resource}", $data);

            return $response->json() ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function platformAdminPut(string $resource, ?int $siteId, array $data): array
    {
        if (! $siteId || ! $this->platformUrl()) {
            return [];
        }

        try {
            $response = Http::withToken($this->platformToken())
                ->timeout(15)
                ->put("{$this->platformUrl()}/api/v1/education/site-admin/{$siteId}/{$resource}", $data);

            return $response->json() ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function platformAdminDelete(string $resource, ?int $siteId): bool
    {
        if (! $siteId || ! $this->platformUrl()) {
            return false;
        }

        try {
            $response = Http::withToken($this->platformToken())
                ->timeout(15)
                ->delete("{$this->platformUrl()}/api/v1/education/site-admin/{$siteId}/{$resource}");

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function clearArticleCache(int $siteId): void
    {
        Cache::forget("edu_center_articles_{$siteId}");
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
