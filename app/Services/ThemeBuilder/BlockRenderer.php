<?php

namespace App\Services\ThemeBuilder;

use App\Helpers\CurrencyHelper;
use App\Models\Post;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SaaSPlatform\ThemeRuntime\Attributes\HostOwnedOverride;
use SaaSPlatform\ThemeRuntime\Security\HtmlSanitizer;

/**
 * رندرکنندهٔ بلوک‌های Theme Builder در سایت مشتری.
 * از payload ارسالی از saas-platform برای ساخت هدر/فوتر استفاده می‌کند.
 * قبل از رندر، بلوک با ElementSchemaRegistry اعتبارسنجی می‌شود؛ در صورت خطا یا exception
 * به‌جای خراب شدن صفحه، بلوک placeholder نمایش داده می‌شود.
 *
 * Phase 2h (ADR-029) — now extends the shared canonical
 * \SaaSPlatform\ThemeRuntime\BlockRenderer. This class's own render*()
 * methods below still take precedence for every type it defines (normal
 * PHP method overriding — nothing here changed behavior), but any
 * canonical type NOT (or no longer) defined here falls back to the shared,
 * single-authority implementation instead of silently missing. Its own
 * renderBlock()/renderBlockUnsafe() dispatch wrapper (schema validation,
 * exception→placeholder safety) is intentionally NOT replaced by the
 * shared base's simpler dispatch — that safety wrapper is a genuine,
 * valuable customer-site-runtime concern the Builder preview doesn't need.
 */
class BlockRenderer extends \SaaSPlatform\ThemeRuntime\BlockRenderer
{
    protected array $context = [];

    /**
     * Nullable to stay signature-compatible with the shared base class
     * (SaaSPlatform\ThemeRuntime\BlockRenderer declares `protected ?string $mode`),
     * so a future composer update of the path repo cannot fatal on a
     * typed-property invariance violation. NULL means "not forced": editor
     * output is then driven solely by the context['editor_mode'] flag.
     */
    protected ?string $mode = 'editor';

    /** @var ElementSchemaRegistry|null */
    protected $schemaRegistry;

    /**
     * Phase 3E-H-C — resolved once and reused for every extension element
     * rendered within this BlockRenderer instance's lifetime (one per
     * request), so HasAssets contributions accumulate across all blocks on
     * the page instead of being discarded per-block. BlockRenderer itself
     * is resolved fresh per request (never bound as a singleton), so this
     * carries no cross-request/cross-tenant leakage risk.
     */
    protected ?\App\Builder\Rendering\ElementRenderer $elementRenderer = null;

    /**
     * Manifest-declared Extension assets ({ext:* type => {css, js}}), read
     * from the Theme's own `extension_assets` payload (see
     * ThemeCanvasRenderer::setThemeData()) — forwarded into
     * ElementRenderer so an ext:* renderer never needs to implement
     * HasAssets merely to repeat the exact same assets its manifest
     * already declares. Empty by default: every non-Canvas caller of this
     * class (and every Theme predating this feature) is unaffected.
     */
    protected array $extensionAssetsByType = [];

    public function setSchemaRegistry(?ElementSchemaRegistry $registry): self
    {
        $this->schemaRegistry = $registry;
        return $this;
    }

    public function setExtensionAssets(array $assetsByType): self
    {
        $this->extensionAssetsByType = $assetsByType;
        return $this;
    }

    public function setMode(?string $mode): self
    {
        if ($mode !== null && ! in_array($mode, ['editor', 'public'], true)) {
            throw new \InvalidArgumentException("Unsupported block-render mode [{$mode}].");
        }

        $this->mode = $mode;

        return $this;
    }

    /**
     * Phase 3E-H-C — CSS/JS assets declared (via HasAssets) by every
     * extension element rendered so far in this request, deduplicated.
     * Empty when no extension element with assets has rendered.
     */
    public function getCollectedAssets(): array
    {
        return $this->elementRenderer?->getCollectedAssets() ?? ['css' => [], 'js' => []];
    }

    /**
     * رندر آرایه‌ای از بلوک‌ها (مثلاً header_blocks یا footer_blocks).
     */

    #[HostOwnedOverride(
        reason: 'Schema-validates every block before render and wraps a crashing renderer in a safe placeholder instead of a broken page — a real tenant-runtime safety net the Builder preview does not need.',
        category: HostOwnedOverride::REASON_DISPATCH_INFRASTRUCTURE,
    )]
    public function renderBlock(array $block): string
    {
        $type = $block['type'] ?? 'text';
        $id = $block['id'] ?? Str::random(8);

        $registry = $this->schemaRegistry ?? app()->make(ElementSchemaRegistry::class);
        if (!$registry->validate($block)) {
            Log::warning('Builder block failed schema validation', [
                'element_id' => $id,
                'type' => $type,
                'errors' => $registry->getLastErrors(),
            ]);
            $html = $this->renderPlaceholder($id, $type, 'invalid_schema');
        } else {
            try {
                $html = $this->renderBlockUnsafe($block);
            } catch (\Throwable $e) {
                Log::error('Builder block render failed', [
                    'element_id' => $id,
                    'type' => $type,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $html = $this->renderPlaceholder($id, $type, 'render_error');
            }
        }

        $html = $this->applyBlockStyle($html, $block['style'] ?? [], $block['responsive'] ?? [], $id);
        return $this->wrapEditorMode($html, $id, $type);
    }

    /**
     * Editor mode exposes identity plus editing hooks. Public mode exposes
     * identity only, allowing the runtime to map the DOM without enabling an
     * authoring surface.
     */
    #[HostOwnedOverride(
        reason: 'Marks container blocks for the canvas iframe\'s nested drop zones — container detection itself is delegated to the shared isContainerType() authority (Phase 3F-B), not a local list.',
        category: HostOwnedOverride::REASON_HOST_CONTEXT,
    )]
    protected function wrapEditorMode(string $html, string $id, string $type): string
    {
        $isEditor = $this->mode === 'editor' && ($this->context['editor_mode'] ?? false);
        $isPublicRuntime = $this->mode === 'public';
        if (! $isEditor && ! $isPublicRuntime) {
            return $html;
        }
        $esc   = htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $idEsc = htmlspecialchars($id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $containerAttr = self::isContainerType($type) ? ' data-tb-container="1"' : '';
        $metadata = " data-tb-block-id=\"{$idEsc}\" data-tb-block-type=\"{$esc}\"{$containerAttr}";

        // Keep editor hooks metadata-only. Adding a class here can collide
        // with theme selectors and change typography or layout in preview.
        $decorated = preg_replace('/^(\s*<[a-zA-Z][a-zA-Z0-9:-]*)(?=\s|>)/', '$1'.$metadata, $html, 1, $rootCount);
        if ($rootCount === 1) {
            return $decorated;
        }

        return "<div{$metadata}>{$html}</div>";
    }

    /**
     * Safe fallback UI when element is invalid or crashes. Does not break the page.
     */
    protected function renderPlaceholder(string $id, string $type, string $reason): string
    {
        $reasonLabel = $reason === 'invalid_schema' ? 'invalid' : 'error';
        $safeType = $this->escapeAttr($type);
        $safeId = $this->escapeAttr($id);
        return "<div id=\"tb-{$safeId}\" class=\"tb-placeholder tb-placeholder-{$reasonLabel}\" data-type=\"{$safeType}\" data-reason=\"{$this->escapeAttr($reason)}\" aria-hidden=\"true\"><span class=\"tb-placeholder-label\">[{$reasonLabel}] {$safeType}</span></div>";
    }

    /**
     * Actual render logic. Called inside try/catch by renderBlock().
     * Uses Element SDK (ElementRenderer) when type is registered; otherwise legacy render methods.
     */
    protected function renderBlockUnsafe(array $block): string
    {
        $type = $block['type'] ?? 'text';
        $id = $block['id'] ?? Str::random(8);
        $props = $block['props'] ?? $block['data'] ?? [];
        if (isset($block['style']) && is_array($block['style'])) {
            $props['_style'] = $block['style'];
        }
        // TB4: children may be at top-level (block.children), legacy (block.blocks), or in props.blocks
        $children = $block['children'] ?? $block['blocks'] ?? ($props['blocks'] ?? []);
        // TASK 3/3 Phase 5 (BLD-16 follow-up): renderTabs() (inherited from
        // the shared package) only ever receives $props, never the raw
        // $block, but needs each child's own data (e.g. a tab_panel's
        // title) to build a real nav bar — not just $inner's already-
        // rendered HTML. Writing the resolved children back into
        // $props['blocks'] makes that one guaranteed channel regardless of
        // which of the three shapes above the block actually used. Real
        // browser certification caught this: canonical Canvas-authored
        // tabs (top-level `children`) rendered a nav bar with empty
        // labels, since renderTabs() only ever checked `props.blocks`.
        if (!empty($children)) {
            $props['blocks'] = $children;
        }

        $inner = '';
        if (self::isContainerType($type) && !empty($children)) {
            $inner = $this->renderBlocks($children);
        }
        // BLD-01 (Full Product Commercial Audit, TASK 1/3): `accordion` is
        // rendered correctly right here — but the generic dispatch a few
        // lines below (`$method = 'render'.Str::studly($type);
        // $this->{$method}($props, $inner, $id)`) used to ALSO resolve to
        // this exact same inherited method and re-invoke it with
        // `($props, $inner, $id)`. PHP silently discards the two extra
        // positional args against `renderAccordion(array $items)`'s
        // single-array signature, so the WHOLE `$props` array (not
        // `$props['items']`) was rebound to the method's only parameter —
        // discarding the correct `$inner` computed here and rendering one
        // phantom untitled panel instead. Returning the already-correct
        // `$inner` immediately, the same way a container type's children
        // are returned as `$inner`, is the fix — it still passes through
        // `renderBlock()`'s uniform applyBlockStyle()/wrapEditorMode()
        // wrapping unchanged.
        //
        // TASK 2A (tabs container authoring fix): `tabs` no longer has a
        // special case here — it is a real structural container now (see
        // isContainerType() above, delegating to the shared
        // BlockRegistry::isContainerType()), so it must fall through to the
        // generic dispatch below like every other container, where the
        // inherited SaaSPlatform\ThemeRuntime\BlockRenderer::renderTabs()
        // receives the standard ($props, $inner, $id) — $inner already
        // holding every real tab_panel child's rendered HTML. The special
        // case that used to live here (`return $this->renderTabs($props['tabs'])`)
        // called the OLD single-array `renderTabs(array $tabs)` signature,
        // which the shared package no longer has — it silently discarded
        // `$inner` and returned empty for any block using the new
        // tab_panel-children shape, dropping real content on this host's
        // live render path. Legacy `props.tabs`-only content (no tab_panel
        // children yet) is still read correctly: it falls through to the
        // shared renderTabs()'s own read-compat fallback (triggered when
        // `$inner === ''`), the exact same one path this fix converges on.
        if ($type === 'accordion') {
            return !empty($props['items']) ? $this->renderAccordion($props['items']) : '';
        }
        // Builder integrity fix (2026-09-12): a `slider` slide is plain
        // {image,title,subtitle,link} data, not a self-describing block —
        // treating it as one here fed each slide through schema validation
        // (which requires a `type` key) and logged a spurious "failed
        // schema validation" warning per slide, for a value ($inner) that
        // renderSlider() never even consumed. See ThemeCanvasRenderer::
        // renderSlider() for the actual (fixed) slide rendering.

        if (\App\Builder\ElementRegistry::has($type)) {
            $context = array_merge($this->context, [
                'id' => $id,
                'inner' => $inner,
                'style' => $block['style'] ?? [],
            ]);
            $this->elementRenderer ??= app(\App\Builder\Rendering\ElementRenderer::class);
            return $this->elementRenderer
                ->setContext($this->context)
                ->setExtensionAssetMap($this->extensionAssetsByType)
                ->render($type, $props, $context);
        }

        $method = 'render' . Str::studly($type);
        if (method_exists($this, $method)) {
            return $this->{$method}($props, $inner, $id);
        }

        return $this->renderFallback($type, $props, $inner, $id);
    }

    /**
     * Phase 3F-B — the one container-capability authority for this host,
     * replacing what used to be a raw 7-type array literal duplicated
     * across this method, wrapEditorMode(), and the Canvas editor's own
     * client-side JS. Layered, not merged into a single registry:
     *
     *   1. Canonical/shared layer — SaaSPlatform\ThemeRuntime\BlockRegistry
     *      ::isContainerType() (shared package, also consumed by
     *      saas-platform's own Canvas authoring): section/container/grid/
     *      columns/flex_container.
     *   2. Host/extension layer — App\Builder\ElementRegistry::definition()
     *      ::isContainer, driven by HasContainerCapability (3E-D): covers
     *      customer-site-only host types (row/column) and any registered
     *      ext:* extension type (3E-H-C), uniformly, through the exact same
     *      declarative contract — no separate extension-only check needed
     *      anymore.
     *
     * An unregistered/unavailable type — including an unregistered ext:* —
     * is never a container (ElementRegistry::definition() returns null),
     * the same safe fail-closed default as before. Existing behavior for
     * every currently-recognized type is unchanged; nothing here enforces
     * allowed_children (never has).
     */
    protected static function isContainerType(string $type): bool
    {
        if (\SaaSPlatform\ThemeRuntime\BlockRegistry::isContainerType($type)) {
            return true;
        }
        $definition = \App\Builder\ElementRegistry::definition($type);
        return $definition !== null && $definition->isContainer;
    }





    /**
     * گرید محصولات فروشگاه (با استفاده از ماژول shop سایت مشتری).
     *
     * props:
     * - columns: تعداد ستون‌ها (پیش‌فرض 4)
     * - limit: تعداد محصولات (پیش‌فرض 8)
     */

    /**
     * Blog posts block: real posts when blog module is active.
     */
    #[HostOwnedOverride(
        reason: 'Queries real tenant Post data (with module-enabled gate) — the canonical default is an honest, non-fabricated placeholder for a host with no such data (the Builder preview). Real tenant-data adapter.',
        category: HostOwnedOverride::REASON_TENANT_DATA,
    )]
    protected function renderBlogPosts(array $props, string $inner, string $id): string
    {
        if (!\App\Helpers\SiteHelper::blogEnabled()) {
            return "<div id=\"tb-{$id}\" class=\"tb-blog-posts-empty\"><p class=\"tb-text\">ماژول وبلاگ غیرفعال است.</p></div>";
        }
        $limit = max(1, min(12, (int) ($props['limit'] ?? 6)));
        $title = $this->escape($props['title'] ?? 'آخرین مطالب');
        // Builder integrity audit (2026-09-11): 'layout' is a real,
        // customer-site-declared schema field this method never consumed.
        // Universal Multipurpose Slider Overhaul: 'slider' is a new third
        // option alongside the pre-existing 'grid'/'list' — real carousel
        // via the shared BlockRenderer::renderCarousel(), same card markup.
        $layout = $props['layout'] ?? 'grid';
        if (!in_array($layout, ['grid', 'list', 'slider'], true)) {
            $layout = 'grid';
        }
        $source = $props['source'] ?? 'latest';

        $query = Post::published();
        if ($source === 'category' && !empty($props['category_id'])) {
            $query->where('category_id', (int) $props['category_id']);
        } elseif ($source === 'manual' && !empty($props['manual_ids']) && is_array($props['manual_ids'])) {
            $ids = array_values(array_filter(array_map('intval', $props['manual_ids'])));
            if (empty($ids)) {
                return "<div id=\"tb-{$id}\" class=\"tb-blog-posts\"><h2 class=\"tb-heading\">{$title}</h2></div>";
            }
            // A deleted manually-selected post is simply absent from the
            // result set — never a renderer crash.
            $query->whereIn('id', $ids)->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')');
        } else {
            $query->latest('published_at');
        }
        $posts = $query->take($limit)->get();

        if ($posts->isEmpty()) {
            return "<div id=\"tb-{$id}\" class=\"tb-blog-posts\"><h2 class=\"tb-heading\">{$title}</h2><div class=\"tb-blog-posts-empty\"><p class=\"tb-text\">هنوز مطلبی منتشر نشده است.</p><a href=\"" . $this->escapeAttr(route('blog.index')) . "\" class=\"tb-btn tb-btn-primary\">وبلاگ</a></div></div>";
        }
        $cards = [];
        foreach ($posts as $post) {
            $url = route('blog.show', $post->slug);
            $t = $this->escape($post->title);
            $excerpt = $this->escape($post->previewText(120));
            $date = $post->published_at ? \App\Helpers\Jalali::formatFa($post->published_at, 'Y/m/d') : '';
            $img = $this->imageWithFallback(
                $post->featured_image ? \App\Helpers\ImageHelper::url($post->featured_image) : '',
                $post->title,
                'aspect-ratio:16/9;',
                '📰'
            );
            $cards[] = "<a href=\"{$url}\" class=\"tb-blog-card\">{$img}<div class=\"tb-blog-card-body\"><h3 class=\"tb-blog-card-title\">{$t}</h3><p class=\"tb-blog-card-meta\">{$date}</p><p class=\"tb-blog-card-excerpt\">{$excerpt}</p></div></a>";
        }

        if ($layout === 'slider') {
            $carousel = $this->renderCarousel(
                $cards,
                $id . '-track',
                (bool) ($props['autoplay'] ?? true),
                (int) ($props['interval'] ?? 5000),
                'tb-blog-slider',
                ['desktop' => 3, 'tablet' => 2, 'mobile' => 1]
            );
            return "<div id=\"tb-{$id}\" class=\"tb-blog-posts\"><h2 class=\"tb-heading tb-section-title\">{$title}</h2>{$carousel}</div>";
        }

        $colsClass = $layout === 'list' ? 'tb-grid-cols-1' : 'tb-grid-cols-3';
        $html = "<h2 class=\"tb-heading tb-section-title\">{$title}</h2><div class=\"tb-grid {$colsClass} tb-gap-lg tb-blog-grid\">" . implode('', $cards) . "</div>";
        return "<div id=\"tb-{$id}\" class=\"tb-blog-posts\">{$html}</div>";
    }

    /**
     * Testimonials / reviews block: real approved reviews when module active.
     */
    #[HostOwnedOverride(
        reason: 'Queries real tenant Review data (with module-enabled gate) — same reasoning as renderBlogPosts(). Real tenant-data adapter.',
        category: HostOwnedOverride::REASON_TENANT_DATA,
    )]
    protected function renderTestimonials(array $props, string $inner, string $id): string
    {
        $title = $this->escape($props['title'] ?? 'نظرات مشتریان');
        $limit = (int) ($props['limit'] ?? 5);
        if (!\App\Helpers\SiteHelper::moduleEnabled('reviews') || !class_exists(Review::class)) {
            return "<div id=\"tb-{$id}\" class=\"tb-testimonials\"><h2 class=\"tb-heading\">{$title}</h2><p class=\"tb-text\">ماژول نظرات غیرفعال است.</p></div>";
        }
        $reviews = Review::where('is_approved', true)->latest()->take($limit)->get();
        if ($reviews->isEmpty()) {
            return "<div id=\"tb-{$id}\" class=\"tb-testimonials\"><h2 class=\"tb-heading\">{$title}</h2><div class=\"tb-testimonials-empty\"><p class=\"tb-text\">هنوز نظری ثبت نشده است.</p></div></div>";
        }
        $items = [];
        foreach ($reviews as $r) {
            $name = $this->escape($r->author_name ?? 'کاربر');
            $body = $this->escape(\Illuminate\Support\Str::limit($r->body ?? '', 200));
            $rating = (int) ($r->rating ?? 5);
            $stars = str_repeat('★', min(5, max(0, $rating))) . str_repeat('☆', 5 - min(5, max(0, $rating)));
            $items[] = "<div class=\"tb-testimonial-card\"><div class=\"tb-testimonial-rating\">{$stars}</div><p class=\"tb-testimonial-text\">{$body}</p><p class=\"tb-testimonial-author\">— {$name}</p></div>";
        }
        $html = "<h2 class=\"tb-heading tb-section-title\">{$title}</h2><div class=\"tb-testimonials-grid\">" . implode('', $items) . "</div>";
        return "<div id=\"tb-{$id}\" class=\"tb-testimonials\">{$html}</div>";
    }

    /**
     * FAQ block: accordion from props or empty state.
     */
    #[HostOwnedOverride(
        reason: 'Richer real behavior than canonical: a helpful empty-state hint (matching this codebase\'s established EMPTY_POLICY_PLACEHOLDER convention) and title/content field aliases. Not audited/merged into canonical this phase — residual for a future pass.',
        category: HostOwnedOverride::REASON_UNMERGED_CAPABILITY,
    )]
    protected function renderFaq(array $props, string $inner, string $id): string
    {
        $title = $this->escape($props['title'] ?? 'سوالات متداول');
        $items = $props['items'] ?? [];
        if (empty($items)) {
            return "<div id=\"tb-{$id}\" class=\"tb-faq\"><h2 class=\"tb-heading tb-section-title\">{$title}</h2><p class=\"tb-text\">سوالات متداول از پنل مدیریت قابل ویرایش است.</p></div>";
        }
        $out = ["<h2 class=\"tb-heading tb-section-title\">{$title}</h2>"];
        foreach ($items as $i => $item) {
            $q = $this->escape($item['question'] ?? $item['title'] ?? 'سوال');
            $a = $this->sanitizeHtml($item['answer'] ?? $item['content'] ?? '');
            $out[] = "<details class=\"tb-faq-item\"><summary class=\"tb-faq-question\">{$q}</summary><div class=\"tb-faq-answer\">{$a}</div></details>";
        }
        return "<div id=\"tb-{$id}\" class=\"tb-faq\">" . implode('', $out) . "</div>";
    }


    /** Page builder: row wrapper (flex row). */
    protected function renderRow(array $props, string $inner, string $id): string
    {
        $gap = $this->spacingClass($props['gap'] ?? 'md');
        return "<div id=\"tb-{$id}\" class=\"tb-row tb-gap-{$gap}\">{$inner}</div>";
    }

    /** Page builder: column with optional width ratio (e.g. 1/2, 1/3). */
    protected function renderColumn(array $props, string $inner, string $id): string
    {
        $width = $props['width'] ?? '1/1';
        $widthClass = 'tb-col-full';
        if (preg_match('#^(\d+)/(\d+)$#', $width, $m)) {
            $w = (int) $m[1];
            $t = (int) $m[2];
            if ($t > 0 && $w > 0 && $w <= $t) {
                $widthClass = "tb-col-{$w}-{$t}";
            }
        }
        return "<div id=\"tb-{$id}\" class=\"tb-column {$widthClass}\">{$inner}</div>";
    }







    protected function renderSpacer(array $props, string $inner, string $id): string
    {
        $height = $props['height'] ?? 'md';
        $map = ['none' => '0', 'xs' => '0.5rem', 'sm' => '1rem', 'md' => '1.5rem', 'lg' => '2.5rem', 'xl' => '4rem'];
        $h = $map[$height] ?? '1.5rem';
        return "<div id=\"tb-{$id}\" class=\"tb-spacer\" style=\"height:{$h}\" aria-hidden=\"true\"></div>";
    }


    #[HostOwnedOverride(
        reason: 'Real route()-based form action (shop or home, depending on real host state via SiteHelper::shopEnabled()) — the canonical version has no real search backend to target (action="#"), appropriate only for the Builder preview.',
        category: HostOwnedOverride::REASON_HOST_CONTEXT,
    )]
    protected function renderSearchBar(array $props, string $inner, string $id): string
    {
        $placeholder = $this->escapeAttr($props['placeholder'] ?? 'جستجو...');
        $action = \App\Helpers\SiteHelper::shopEnabled() ? route('shop.index') : route('home');
        return "<div id=\"tb-{$id}\" class=\"tb-search-bar-wrap\"><form method=\"get\" action=\"{$action}\" role=\"search\"><input type=\"search\" name=\"q\" placeholder=\"{$placeholder}\" aria-label=\"جستجو\"></form></div>";
    }

    /**
     * Commercial Productization fase-2: the shared package gained a
     * canonical renderCartIcon() this session (closing a real "raw JSON
     * dump" preview bug that has no real cart to query). This host-owned
     * override predates that and queries the real session cart / shop
     * route — a genuine tenant-data adapter, not a duplicate to delete.
     */
    #[HostOwnedOverride(
        reason: 'Queries the real session cart quantity and a real shop.cart route, gated on SiteHelper::shopEnabled() — the canonical version has no real cart to query (Builder preview has no session cart), so it renders a static, non-functional icon. Real tenant-data adapter.',
        category: HostOwnedOverride::REASON_TENANT_DATA,
    )]
    protected function renderCartIcon(array $props, string $inner, string $id): string
    {
        if (!\App\Helpers\SiteHelper::shopEnabled()) {
            return '';
        }
        $cartUrl = route('shop.cart');
        $count = 0;
        foreach (session('cart', []) as $item) {
            $count += (int) ($item['quantity'] ?? 0);
        }
        $badge = $count > 0 ? "<span style=\"background:#ef4444;color:#fff;font-size:0.7rem;padding:0.15rem 0.4rem;border-radius:999px;\">{$count}</span>" : '';
        return "<a id=\"tb-{$id}\" href=\"{$cartUrl}\" class=\"tb-cart-link\" aria-label=\"سبد خرید\">🛒 سبد خرید {$badge}</a>";
    }






    /**
     * Phase 3H-S1 (security closure) — was previously an independent
     * `strip_tags()`-only implementation with a wider allowlist and NO
     * attribute stripping, so `on*` event-handler attributes and
     * `javascript:`/`data:`/`vbscript:` URL schemes survived verbatim on
     * every allowed tag except the one this method never rebuilt. Delegates
     * entirely to the canonical `HtmlSanitizer::sanitizeRichHtml()` — the
     * one intended sole rich-HTML sanitization authority for the whole
     * Theme Runtime — instead of maintaining a second, independently
     * drifting tag/attribute/URL-scheme policy. This narrows the allowed
     * tag set to canonical's own (`<p><br><a><strong><em><ul><ol><li><h2>
     * <h3>`, dropping `<h4><div><span><blockquote>`) — a deliberate,
     * security-motivated behavior change, not an oversight.
     */
    protected function renderCustomHtml(array $props, string $inner, string $id): string
    {
        $raw = (string) ($props['content'] ?? '');
        $clean = HtmlSanitizer::sanitizeRichHtml($raw);

        return "<div id=\"tb-{$id}\" class=\"tb-custom-html\">{$clean}</div>";
    }



    // Alias so Str::studly('product_grid') → renderProductGrid works
    /**
     * Phase 2i: was a forwarder to renderProduct_grid() — merged directly
     * here since that underscore name was never itself dispatchable
     * (Str::studly('product_grid') => 'ProductGrid') and the forwarder had
     * no adapter responsibility of its own. Customer-runtime-only: queries
     * real Product data.
     *
     * Commercial Productization fase-2: the shared package gained a
     * canonical renderProductGrid() this session (a real demo-fixture
     * renderer, closing a "raw JSON dump" preview bug — saas-platform has
     * no Product model to query for real). This host override is the real
     * tenant-data counterpart, not a duplicate to delete.
     */
    #[HostOwnedOverride(
        reason: 'Queries the real tenant Product model (with category/price/stock) — the canonical version renders a static demo fixture since saas-platform has no Product model to query. Real tenant-data adapter, same reasoning as renderBlogPosts().',
        category: HostOwnedOverride::REASON_TENANT_DATA,
    )]
    protected function renderProductGrid(array $props, string $inner, string $id): string
    {
        $limit = (int) ($props['limit'] ?? 8);
        $columns = (int) ($props['columns'] ?? 4);
        $colsClass = "tb-grid-cols-{$columns}";

        if (!\App\Helpers\SiteHelper::shopEnabled()) {
            return "<div id=\"tb-{$id}\" class=\"tb-grid {$colsClass} tb-gap-md tb-product-grid-wrap\"><p class=\"tb-text\">ماژول فروشگاه غیرفعال است.</p></div>";
        }

        $query = Product::query()->active();
        $products = $query->latest()->take($limit)->get();

        if ($products->isEmpty()) {
            $shopUrl = route('shop.index');
            $empty = "<div class=\"tb-product-grid-empty\"><p>هنوز محصولی در این بخش ثبت نشده است.</p><a href=\"{$shopUrl}\" class=\"tb-btn tb-btn-primary\">مشاهده فروشگاه</a></div>";
            return "<div id=\"tb-{$id}\" class=\"tb-grid {$colsClass} tb-gap-md tb-product-grid-wrap\">{$empty}</div>";
        }

        $cards = [];
        foreach ($products as $product) {
            $hasDiscount = $product->sale_price !== null && (float) $product->sale_price < (float) $product->price;
            $cards[] = $this->productCardHtml(
                $product->title,
                $product->image ? \App\Helpers\ImageHelper::url($product->image) : '',
                (float) $product->effective_price,
                $hasDiscount ? (float) $product->price : null,
                $product->is_new ? 'جدید' : null,
                null,
                route('shop.show', $product->slug),
                CurrencyHelper::formatPrice($product->effective_price) . ' ' . CurrencyHelper::unitLabel(),
                $hasDiscount ? CurrencyHelper::formatPrice($product->price) . ' ' . CurrencyHelper::unitLabel() : null
            );
        }

        return "<div id=\"tb-{$id}\" class=\"tb-grid {$colsClass} tb-gap-md tb-product-grid-wrap\">" . implode('', $cards) . "</div>";
    }

    // Alias so Str::studly('blog_posts') falls through correctly

    // Alias so Str::studly('social_icons') → renderSocialIcons works

    // Alias so Str::studly('nav_menu') → renderNavMenu works

    // Alias so Str::studly('promo_banner') → renderPromoBanner works

    // Alias so Str::studly('search_bar') → renderSearchBar works

    // Alias so Str::studly('cart_icon') → renderCartIcon works

    /**
     * Course list block — shows real courses from the education module.
     */
    protected function renderCourseList(array $props, string $inner, string $id): string
    {
        return $this->renderCourses($props, $inner, $id);
    }

    protected function renderCourses(array $props, string $inner, string $id): string
    {
        $title   = $this->escape($props['title'] ?? 'دوره‌های آموزشی');
        $limit   = (int) ($props['limit'] ?? 6);
        $columns = (int) ($props['columns'] ?? 3);

        if (!\App\Helpers\SiteHelper::coursesEnabled() || !class_exists(\App\Models\Course::class)) {
            return "<div id=\"tb-{$id}\" class=\"tb-courses\"><h2 class=\"tb-heading\">{$title}</h2><p class=\"tb-text\">ماژول آموزش فعال نیست.</p></div>";
        }

        $courses = \App\Models\Course::where('is_published', true)->latest()->take($limit)->get();
        if ($courses->isEmpty()) {
            return "<div id=\"tb-{$id}\" class=\"tb-courses\"><h2 class=\"tb-heading tb-section-title\">{$title}</h2><p class=\"tb-text\">هنوز دوره‌ای منتشر نشده.</p></div>";
        }

        $colsClass = "tb-grid-cols-{$columns}";
        $cards = [];
        foreach ($courses as $course) {
            try { $url = route('courses.show', $course->slug); } catch (\Throwable $e) { $url = '#'; }
            $name = $this->escape($course->title ?? $course->name ?? '');
            $img  = !empty($course->cover_image ?? $course->thumbnail ?? '') ? \App\Helpers\ImageHelper::url($course->cover_image ?? $course->thumbnail) : '';
            $imgHtml = $img ? "<div class=\"tb-course-card-img\"><img src=\"{$this->escapeAttr($img)}\" alt=\"{$name}\" loading=\"lazy\"></div>" : "<div class=\"tb-course-card-img tb-course-noimg\">🎓</div>";
            $price = '';
            if (isset($course->price)) {
                $price = $course->price > 0 ? '<span class="tb-course-price">' . \App\Helpers\CurrencyHelper::formatPrice($course->price) . ' ' . \App\Helpers\CurrencyHelper::unitLabel() . '</span>' : '<span class="tb-course-price tb-course-free">رایگان</span>';
            }
            $cards[] = "<a href=\"{$url}\" class=\"tb-course-card\">{$imgHtml}<div class=\"tb-course-card-body\"><h3 class=\"tb-course-title\">{$name}</h3>{$price}</div></a>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-courses\"><h2 class=\"tb-heading tb-section-title\">{$title}</h2><div class=\"tb-grid {$colsClass} tb-gap-md tb-courses-grid\">" . implode('', $cards) . "</div></div>";
    }

    /**
     * Blog categories block — shows real categories with post counts.
     */
    protected function renderBlogCategories(array $props, string $inner, string $id): string
    {
        $title = $this->escape($props['title'] ?? 'دسته‌بندی وبلاگ');
        $limit = (int) ($props['limit'] ?? 8);

        if (!\App\Helpers\SiteHelper::blogEnabled()) {
            return "<div id=\"tb-{$id}\" class=\"tb-blog-categories\"><h2 class=\"tb-heading\">{$title}</h2><p class=\"tb-text\">ماژول وبلاگ غیرفعال است.</p></div>";
        }

        if (!class_exists(\App\Models\Category::class) && !class_exists(\App\Models\PostCategory::class)) {
            return "<div id=\"tb-{$id}\" class=\"tb-blog-categories\"><h2 class=\"tb-heading\">{$title}</h2><p class=\"tb-text\">دسته‌بندی یافت نشد.</p></div>";
        }

        $modelClass = class_exists(\App\Models\PostCategory::class) ? \App\Models\PostCategory::class : \App\Models\Category::class;
        try {
            $cats = $modelClass::withCount('posts')->orderByDesc('posts_count')->take($limit)->get();
        } catch (\Throwable $e) {
            return "<div id=\"tb-{$id}\" class=\"tb-blog-categories\"><h2 class=\"tb-heading\">{$title}</h2></div>";
        }

        $items = [];
        foreach ($cats as $cat) {
            try { $url = route('blog.category', $cat->slug); } catch (\Throwable $e) { $url = '#'; }
            $name  = $this->escape($cat->name ?? $cat->title ?? '');
            $count = $cat->posts_count ?? 0;
            $items[] = "<a href=\"{$url}\" class=\"tb-category-pill\">{$name}<span class=\"tb-category-count\">{$count}</span></a>";
        }

        $html = $items ? implode('', $items) : '<p class="tb-text">دسته‌بندی یافت نشد.</p>';
        return "<div id=\"tb-{$id}\" class=\"tb-blog-categories\"><h2 class=\"tb-heading tb-section-title\">{$title}</h2><div class=\"tb-categories-grid\">{$html}</div></div>";
    }

    /**
     * Product categories block — shows real shop categories.
     */
    protected function renderProductCategories(array $props, string $inner, string $id): string
    {
        $title   = $this->escape($props['title'] ?? 'دسته‌بندی محصولات');
        $limit   = (int) ($props['limit'] ?? 8);
        $columns = (int) ($props['columns'] ?? 4);

        if (!\App\Helpers\SiteHelper::shopEnabled() || !class_exists(\App\Models\ProductCategory::class)) {
            return "<div id=\"tb-{$id}\" class=\"tb-product-categories\"><h2 class=\"tb-heading\">{$title}</h2><p class=\"tb-text\">دسته‌بندی یافت نشد.</p></div>";
        }

        try {
            $cats = \App\Models\ProductCategory::withCount('products')->orderByDesc('products_count')->take($limit)->get();
        } catch (\Throwable $e) {
            return "<div id=\"tb-{$id}\" class=\"tb-product-categories\"><h2 class=\"tb-heading\">{$title}</h2></div>";
        }

        $colsClass = "tb-grid-cols-{$columns}";
        $cards = [];
        foreach ($cats as $cat) {
            try { $url = route('shop.category', $cat->slug); } catch (\Throwable $e) { $url = '#'; }
            $name  = $this->escape($cat->name ?? $cat->title ?? '');
            $count = $cat->products_count ?? 0;
            $img   = !empty($cat->image) ? \App\Helpers\ImageHelper::url($cat->image) : '';
            $imgHtml = $img ? "<div class=\"tb-cat-card-img\"><img src=\"{$this->escapeAttr($img)}\" alt=\"{$name}\" loading=\"lazy\"></div>" : "<div class=\"tb-cat-card-img tb-cat-noimg\">🗂️</div>";
            $cards[] = "<a href=\"{$url}\" class=\"tb-category-card\">{$imgHtml}<div class=\"tb-cat-card-body\"><span class=\"tb-cat-name\">{$name}</span><span class=\"tb-cat-count\">{$count} محصول</span></div></a>";
        }

        $html = $cards ? implode('', $cards) : '<p class="tb-text">دسته‌بندی یافت نشد.</p>';
        return "<div id=\"tb-{$id}\" class=\"tb-product-categories\"><h2 class=\"tb-heading tb-section-title\">{$title}</h2><div class=\"tb-grid {$colsClass} tb-gap-md\">{$html}</div></div>";
    }

    /**
     * Featured products — products with is_featured=true.
     */
    protected function renderFeaturedProducts(array $props, string $inner, string $id): string
    {
        $title   = $this->escape($props['title'] ?? 'محصولات ویژه');
        $limit   = (int) ($props['limit'] ?? 4);
        $columns = (int) ($props['columns'] ?? 4);

        if (!\App\Helpers\SiteHelper::shopEnabled()) {
            return "<div id=\"tb-{$id}\" class=\"tb-featured-products\"><h2 class=\"tb-heading\">{$title}</h2><p class=\"tb-text\">ماژول فروشگاه غیرفعال است.</p></div>";
        }

        try {
            $products = Product::query()->active()->where('is_featured', true)->take($limit)->get();
            if ($products->isEmpty()) {
                $products = Product::query()->active()->latest()->take($limit)->get();
            }
        } catch (\Throwable $e) {
            return "<div id=\"tb-{$id}\" class=\"tb-featured-products\"><h2 class=\"tb-heading\">{$title}</h2></div>";
        }

        $colsClass = "tb-grid-cols-{$columns}";
        $cards = [];
        foreach ($products as $product) {
            try { $url = route('shop.show', $product->slug); } catch (\Throwable $e) { $url = '#'; }
            $name  = $this->escape($product->title ?? $product->name ?? '');
            $price = \App\Helpers\CurrencyHelper::formatPrice($product->effective_price ?? $product->price ?? 0) . ' ' . \App\Helpers\CurrencyHelper::unitLabel();
            $img   = $product->image ? \App\Helpers\ImageHelper::url($product->image) : '';
            $imgHtml = $img ? "<div class=\"tb-product-card-img\"><img src=\"{$this->escapeAttr($img)}\" alt=\"{$name}\" loading=\"lazy\"></div>" : "<div class=\"tb-product-card-img tb-product-card-noimg\">⭐</div>";
            $cards[] = "<a href=\"{$url}\" class=\"tb-product-card tb-featured-card\">{$imgHtml}<div class=\"tb-product-card-inner\"><h3 class=\"tb-product-title\">{$name}</h3><div class=\"tb-product-price\">{$price}</div></div></a>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-featured-products\"><h2 class=\"tb-heading tb-section-title\">{$title}</h2><div class=\"tb-grid {$colsClass} tb-gap-md\">" . implode('', $cards) . "</div></div>";
    }

    /**
     * Latest blog post block — single featured post.
     */
    protected function renderLatestPost(array $props, string $inner, string $id): string
    {
        if (!\App\Helpers\SiteHelper::blogEnabled()) {
            return '';
        }
        $post = Post::published()->latest('published_at')->first();
        if (!$post) {
            return "<div id=\"tb-{$id}\" class=\"tb-latest-post\"><p class=\"tb-text\">هنوز مطلبی منتشر نشده.</p></div>";
        }
        try { $url = route('blog.show', $post->slug); } catch (\Throwable $e) { $url = '#'; }
        $t       = $this->escape($post->title);
        $excerpt = $this->escape(\Illuminate\Support\Str::limit(strip_tags($post->excerpt ?? $post->body ?? ''), 200));
        $date    = $post->published_at ? \App\Helpers\Jalali::formatFa($post->published_at, 'Y/m/d') : '';
        $img     = '';
        if (!empty($post->featured_image)) {
            $imgUrl = \App\Helpers\ImageHelper::url($post->featured_image);
            $img = "<img src=\"{$this->escapeAttr($imgUrl)}\" alt=\"{$t}\" loading=\"lazy\" class=\"tb-latest-post-img\">";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-latest-post\">{$img}<h2 class=\"tb-heading\">{$t}</h2><p class=\"tb-text tb-muted\">{$date}</p><p class=\"tb-text\">{$excerpt}</p><a href=\"{$url}\" class=\"tb-btn tb-btn-primary\">ادامه مطلب</a></div>";
    }

    // ── HVAC / Industry-specific block renderers ─────────────────────────

    /**
     * Phase 2e (ADR-026, saas-platform parity): replaces a previous
     * ad-hoc, HVAC-hardcoded implementation (hardcoded "Acool HVAC"
     * fallback text, fully inline styling that ignored the theme's own
     * design tokens/colors) with the same markup saas-platform's
     * BlockRenderer produces for nav_header, so Builder preview and
     * customer-site rendering stay consistent for every theme, not just
     * one. Uses the tb-* classes this file's own .tb-hero/.tb-btn/etc
     * blocks already rely on.
     */


    /**
     * Phase 2e (ADR-026): the industry-specific "hvac_hero" type was
     * retired at its canonical source in favor of the reusable, registered
     * "hero_trust" — this is that renderer, matching saas-platform exactly.
     * renderHvacHero()/renderHvac_hero() remain below as thin delegates
     * only so no historical hvac_hero-typed row (if one ever existed
     * outside the canonical catalog) silently breaks.
     */

    protected function renderHvacHero(array $props, string $inner, string $id): string
    {
        return $this->renderHeroTrust($props, $inner, $id);
    }


    /**
     * Phase 2f (ADR-027) fix: this used to fabricate 9 hardcoded HVAC-brand
     * partner names ("Carrier", "Trane", "Lennox", ...) whenever no real
     * logos were authored — real business/partner data must never be
     * invented merely to produce non-empty HTML. Renders an honest
     * placeholder instead, matching saas-platform's parity fix for the
     * same block type.
     */


    /** Phase 2e (ADR-026, saas-platform parity) — see renderNavHeader() note above. */


    /** Phase 2e (ADR-026, saas-platform parity) — see renderNavHeader() note above. */
    #[HostOwnedOverride(
        reason: 'Not byte-diffed against canonical this phase (no hardcoded content, no security gap, no dead code found on inspection) — conservatively retained rather than risk an unverified deletion. Residual: candidate for a full diff in a future audit.',
        category: HostOwnedOverride::REASON_UNMERGED_CAPABILITY,
    )]
    protected function renderServiceCards(array $props, string $inner, string $id): string
    {
        $title    = $this->escape($props['title'] ?? '');
        $subtitle = $this->escape($props['subtitle'] ?? '');
        $columns  = max(2, min(4, (int) ($props['columns'] ?? 3)));
        $cards    = is_array($props['cards'] ?? null) ? $props['cards'] : [];

        $headerHtml = ($title || $subtitle)
            ? "<div class=\"tb-features-section-header\">" . ($title ? "<h2 class=\"tb-features-section-title\">{$title}</h2>" : '') . ($subtitle ? "<p class=\"tb-features-section-sub\">{$subtitle}</p>" : '') . "</div>"
            : '';

        $items = '';
        foreach ($cards as $card) {
            $icon   = $this->escape(is_array($card) ? ($card['icon'] ?? '') : '');
            $ctitle = $this->escape(is_array($card) ? ($card['title'] ?? '') : '');
            $desc   = $this->escape(is_array($card) ? ($card['description'] ?? '') : '');
            $url    = $this->escapeAttr(is_array($card) ? ($card['url'] ?? '') : '');
            if ($ctitle === '' && $desc === '') {
                continue;
            }
            $body = ($icon ? "<div class=\"tb-service-icon\">{$icon}</div>" : '')
                . "<h3 class=\"tb-service-title\">{$ctitle}</h3><p class=\"tb-service-text\">{$desc}</p>";
            $items .= $url !== ''
                ? "<a href=\"{$url}\" class=\"tb-service-card\">{$body}</a>"
                : "<div class=\"tb-service-card\">{$body}</div>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-section\">
            <div class=\"tb-container\">{$headerHtml}
                <div class=\"tb-services-grid tb-grid tb-grid-cols-{$columns} tb-gap-md\">{$items}</div>
            </div>
        </div>";
    }



    /** Phase 2e (ADR-026, saas-platform parity) — see renderNavHeader() note above. */
    #[HostOwnedOverride(
        reason: 'Not byte-diffed against canonical this phase — same conservative-retention reasoning as renderServiceCards().',
        category: HostOwnedOverride::REASON_UNMERGED_CAPABILITY,
    )]
    protected function renderProjectCards(array $props, string $inner, string $id): string
    {
        $title    = $this->escape($props['title'] ?? '');
        $subtitle = $this->escape($props['subtitle'] ?? '');
        $columns  = max(2, min(4, (int) ($props['columns'] ?? 2)));
        $cards    = is_array($props['cards'] ?? null) ? $props['cards'] : [];

        $headerHtml = ($title || $subtitle)
            ? "<div class=\"tb-features-section-header\">" . ($title ? "<h2 class=\"tb-features-section-title\">{$title}</h2>" : '') . ($subtitle ? "<p class=\"tb-features-section-sub\">{$subtitle}</p>" : '') . "</div>"
            : '';

        $items = '';
        foreach ($cards as $card) {
            $cat    = $this->escape(is_array($card) ? ($card['category'] ?? '') : '');
            $ctitle = $this->escape(is_array($card) ? ($card['title'] ?? '') : '');
            $desc   = $this->escape(is_array($card) ? ($card['description'] ?? '') : '');
            $url    = $this->escapeAttr(is_array($card) ? ($card['url'] ?? '') : '');
            if ($ctitle === '' && $desc === '') {
                continue;
            }
            $body = ($cat ? "<span class=\"tb-project-card-category\">{$cat}</span>" : '')
                . "<h3 class=\"tb-project-card-title\">{$ctitle}</h3><p class=\"tb-project-card-text\">{$desc}</p>";
            $items .= $url !== ''
                ? "<a href=\"{$url}\" class=\"tb-project-card\">{$body}</a>"
                : "<div class=\"tb-project-card\">{$body}</div>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-section\">
            <div class=\"tb-container\">{$headerHtml}
                <div class=\"tb-project-cards-grid tb-grid tb-grid-cols-{$columns} tb-gap-md\">{$items}</div>
            </div>
        </div>";
    }


    /** Phase 2e (ADR-026, saas-platform parity) — see renderNavHeader() note above. */
    #[HostOwnedOverride(
        reason: 'Not byte-diffed against canonical this phase — same conservative-retention reasoning as renderServiceCards().',
        category: HostOwnedOverride::REASON_UNMERGED_CAPABILITY,
    )]
    protected function renderContactInfo(array $props, string $inner, string $id): string
    {
        $title    = $this->escape($props['title'] ?? '');
        $subtitle = $this->escape($props['subtitle'] ?? '');
        $bg       = $this->escapeAttr($props['background'] ?? '');
        $items    = is_array($props['items'] ?? null) ? $props['items'] : [];

        $headerHtml = ($title || $subtitle)
            ? "<div class=\"tb-features-section-header\">" . ($title ? "<h2 class=\"tb-features-section-title\">{$title}</h2>" : '') . ($subtitle ? "<p class=\"tb-features-section-sub\">{$subtitle}</p>" : '') . "</div>"
            : '';

        $itemsHtml = '';
        foreach ($items as $item) {
            $icon  = $this->escape(is_array($item) ? ($item['icon'] ?? '') : '');
            $label = $this->escape(is_array($item) ? ($item['label'] ?? '') : '');
            $value = $this->escape(is_array($item) ? ($item['value'] ?? '') : '');
            if ($label === '' && $value === '') {
                continue;
            }
            $itemsHtml .= "<div class=\"tb-contact-info-item\">"
                . ($icon ? "<span class=\"tb-contact-info-icon\">{$icon}</span>" : '')
                . "<div>" . ($label ? "<p class=\"tb-contact-info-label\">{$label}</p>" : '') . "<p class=\"tb-contact-info-value\">{$value}</p></div></div>";
        }

        $style = $bg !== '' ? " style=\"background:{$bg};\"" : '';

        return "<div id=\"tb-{$id}\" class=\"tb-section\"{$style}>
            <div class=\"tb-container\">{$headerHtml}
                <div class=\"tb-contact-info-grid\">{$itemsHtml}</div>
            </div>
        </div>";
    }


    /**
     * Phase 2e (ADR-026): the industry-specific "site_footer" type was
     * retired at its canonical source in favor of the reusable, registered
     * "nav_footer" — this is that renderer, matching saas-platform exactly.
     * renderSiteFooter()/renderSite_footer() remain below as thin delegates.
     */

    protected function renderSiteFooter(array $props, string $inner, string $id): string
    {
        return $this->renderNavFooter($props, $inner, $id);
    }


    /**
     * Phase 2e (ADR-026) parity fix: these five types were registered in
     * saas-platform's BlockRegistry (and therefore authorable by the
     * canonical catalog) but had no renderer at all here — a Theme using
     * them would have silently fallen through to the generic fallback on a
     * real customer-facing site, which is exactly the invariant this phase
     * closes: "a Theme that renders correctly in SaaS Builder must not
     * become unsupported when installed/rendered on customer-site."
     */
    #[HostOwnedOverride(
        reason: 'Uses route(\'home\') for a real, environment-aware URL — the canonical default hardcodes "/" since the Builder preview has no real routing concept. Host-context URL generation, exactly the kind of adapter Phase 2h/2i explicitly reserve to hosts.',
        category: HostOwnedOverride::REASON_HOST_CONTEXT,
    )]
    protected function renderBreadcrumb(array $props, string $inner, string $id): string
    {
        $injected = $this->context['breadcrumb_html'] ?? null;
        if ($injected !== null) {
            return "<nav id=\"tb-{$id}\" class=\"tb-breadcrumb\" aria-label=\"مسیر صفحه\">{$injected}</nav>";
        }
        return "<nav id=\"tb-{$id}\" class=\"tb-breadcrumb\" aria-label=\"مسیر صفحه\"><a href=\"" . $this->escapeAttr(route('home')) . "\">خانه</a></nav>";
    }




    /**
     * Authored with only a product_id (often a per-product page template
     * placeholder). Unlike blog_posts/testimonials above, no live product
     * price-resolution service exists in this renderer's context yet — an
     * honest placeholder is rendered rather than fabricating a price.
     */

    // ── End HVAC block renderers ──────────────────────────────────────────

    // ── NEXUS PRO PREMIUM BLOCKS ─────────────────────────────────────────────
    // Ported from saas-platform's app/Services/ThemeBuilder/BlockRenderer.php
    // (same block types, same tb-* CSS classes already defined in
    // resources/views/layouts/canvas-theme.blade.php) so real installed sites
    // stop silently dropping these sections via renderFallback().












    #[HostOwnedOverride(
        reason: 'Includes a real (non-functional-yet, but structurally present) contact form markup the canonical version does not — not byte-diffed/merged this phase, conservatively retained.',
        category: HostOwnedOverride::REASON_UNMERGED_CAPABILITY,
    )]
    protected function renderContactSplit(array $props, string $inner, string $id): string
    {
        $title   = $this->escape($props['title'] ?? 'با ما در ارتباط باشید');
        $subtitle = $this->escape($props['subtitle'] ?? '');
        $phone   = $this->escape($props['phone'] ?? '');
        $email   = $this->escape($props['email'] ?? '');
        $address = $this->escape($props['address'] ?? '');
        $hours   = $this->escape($props['hours'] ?? '');
        $showForm = (bool) ($props['show_form'] ?? true);

        $infoItems = [];
        if ($phone) {
            $infoItems[] = "<div class=\"tb-contact-info-item\"><span class=\"tb-contact-info-icon\">📞</span><div><p class=\"tb-contact-info-label\">تلفن</p><p class=\"tb-contact-info-value\">{$phone}</p></div></div>";
        }
        if ($email) {
            $infoItems[] = "<div class=\"tb-contact-info-item\"><span class=\"tb-contact-info-icon\">✉️</span><div><p class=\"tb-contact-info-label\">ایمیل</p><p class=\"tb-contact-info-value\">{$email}</p></div></div>";
        }
        if ($address) {
            $infoItems[] = "<div class=\"tb-contact-info-item\"><span class=\"tb-contact-info-icon\">📍</span><div><p class=\"tb-contact-info-label\">آدرس</p><p class=\"tb-contact-info-value\">{$address}</p></div></div>";
        }
        if ($hours) {
            $infoItems[] = "<div class=\"tb-contact-info-item\"><span class=\"tb-contact-info-icon\">🕐</span><div><p class=\"tb-contact-info-label\">ساعات کاری</p><p class=\"tb-contact-info-value\">{$hours}</p></div></div>";
        }

        $formHtml = $showForm ? "<div class=\"tb-contact-form-wrap\">
            <div class=\"tb-contact-form-simple\">
                <form onsubmit=\"event.preventDefault();\" novalidate>
                    <div style=\"margin-bottom:1rem;\"><input type=\"text\" placeholder=\"نام شما\" style=\"width:100%;padding:0.65rem 1rem;border:1px solid #e5e7eb;border-radius:0.5rem;font-family:inherit;font-size:0.95rem;\"></div>
                    <div style=\"margin-bottom:1rem;\"><input type=\"text\" placeholder=\"شماره موبایل یا ایمیل\" style=\"width:100%;padding:0.65rem 1rem;border:1px solid #e5e7eb;border-radius:0.5rem;font-family:inherit;font-size:0.95rem;\"></div>
                    <div style=\"margin-bottom:1rem;\"><textarea placeholder=\"پیام شما\" rows=\"4\" style=\"width:100%;padding:0.65rem 1rem;border:1px solid #e5e7eb;border-radius:0.5rem;font-family:inherit;font-size:0.95rem;resize:vertical;\"></textarea></div>
                    <button type=\"submit\" class=\"tb-btn tb-btn-primary\" style=\"width:100%;\">ارسال پیام</button>
                </form>
            </div>
        </div>" : '';

        return "<div id=\"tb-{$id}\" class=\"tb-section\">
            <div class=\"tb-container\">
                <div class=\"tb-features-section-header\">
                    <h2 class=\"tb-features-section-title\">{$title}</h2>
                    " . ($subtitle ? "<p class=\"tb-features-section-sub\">{$subtitle}</p>" : '') . "
                </div>
                <div class=\"tb-contact-split\">
                    <div class=\"tb-contact-info\">" . implode('', $infoItems) . "</div>
                    {$formHtml}
                </div>
            </div>
        </div>";
    }

    // No 'team' data source exists anywhere in the platform today (unlike
    // team_cards, which carries its own members JSON in props) — only a
    // title/limit. Rendering fabricated member names would misrepresent the
    // installed template, so this renders an honest, styled placeholder
    // (mirrors saas-platform's admin-preview treatment of the same block)
    // instead of silently vanishing via renderFallback().

    // ── END NEXUS PRO PREMIUM BLOCKS ─────────────────────────────────────────

    #[HostOwnedOverride(
        reason: 'Renders nothing for a truly unknown/empty type instead of visible debug markup — appropriate for a real tenant-facing page (no raw fallback UI leaking to visitors), whereas the canonical version\'s debug-visible fallback is appropriate for the internal admin Builder preview.',
        category: HostOwnedOverride::REASON_HOST_CONTEXT,
    )]
    protected function renderFallback(string $type, array $props, string $inner, string $id): string
    {
        // اگر نوع بلوک ناشناخته است، سعی می‌کنیم فقط فرزندان را (در صورت وجود) رندر کنیم
        // و در غیر این صورت چیزی نمایش ندهیم تا JSON خام روی صفحه دیده نشود.
        $content = $inner ?: '';
        if ($content === '') {
            return '';
        }
        return "<div id=\"tb-{$id}\" class=\"tb-block tb-block-{$type}\" data-type=\"{$type}\">{$content}</div>";
    }

    // TB5: Build CSS declarations from a style object (shared for desktop + breakpoints)

    // TB5: Apply desktop + responsive breakpoint styles



    /**
     * Phase 2e (ADR-026, saas-platform parity): the standard W3C
     * visually-hidden-but-screen-reader-visible label pattern, ported from
     * saas-platform's BlockRenderer so renderNavHeader()'s search field has
     * a real accessible name here too, not display:none (which would hide
     * it from screen readers as well).
     */



}

