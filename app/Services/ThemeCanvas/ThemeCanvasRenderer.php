<?php

namespace App\Services\ThemeCanvas;

use App\Helpers\CurrencyHelper;
use App\Helpers\ImageHelper;
use App\Helpers\Jalali;
use App\Helpers\SiteHelper;
use App\Models\Course;
use App\Models\HomepageVideo;
use App\Models\Post;
use App\Models\Product;
use App\Services\ThemeBuilder\BlockRenderer;
use Illuminate\Support\Str;

/**
 * Canvas theme block renderer for the customer-site.
 * Extends BlockRenderer and adds rendering for module:* blocks
 * using live data from the customer-site models.
 * Used by ThemeCanvasPageController to render full canvas pages.
 */
class ThemeCanvasRenderer extends BlockRenderer
{
    protected array $themeData = [];

    private ?CustomerMediaResolver $mediaResolver = null;
    private ?InternalLinkResolver $linkResolver = null;

    public function setThemeData(array $themeData): self
    {
        $this->themeData = $themeData;

        // Phase: Extension manifest asset delivery — saas-platform projects
        // each selected Extension release's declared CSS/JS into absolute
        // URLs under `extension_assets` (ExtensionThemeRuntimeAssetProjector)
        // as part of this exact same JSON payload. Absent/empty for every
        // Theme that selects no Extension — a pure no-op for old Themes.
        $this->setExtensionAssets($themeData['extension_assets'] ?? []);

        return $this;
    }

    /**
     * Override renderBlock to intercept module:* types before schema validation.
     * The schema registry doesn't know about module blocks, so we bypass it here.
     * In editor_mode, wraps block with .tb-canvas-block overlay for selection.
     */
    public function renderBlock(array $block): string
    {
        $type = $block['type'] ?? 'text';
        $id   = $block['id'] ?? Str::random(8);
        $block = $this->resolveMediaProps($block, $type);
        $block = $this->resolveInternalLinkProps($block, $type);

        if (str_starts_with($type, 'module:')) {
            try {
                $props = $block['props'] ?? $block['data'] ?? [];
                $html  = $this->renderModuleBlock($type, $props, $id);
            } catch (\Throwable $e) {
                // Builder integrity audit (2026-09-11): this catch
                // previously swallowed every module-block render exception
                // with no log entry at all — a real observability gap
                // found while diagnosing module:shop_product_slider (the
                // parent class's own equivalent catch, in BlockRenderer,
                // does log). Matches that existing pattern instead of
                // introducing a second one.
                \Illuminate\Support\Facades\Log::error('Canvas module block render failed', [
                    'element_id' => $id, 'type' => $type,
                    'message' => $e->getMessage(), 'trace' => $e->getTraceAsString(),
                ]);
                $html = $this->renderPlaceholder($id, $type, 'render_error');
            }
            return $this->wrapEditorMode($html, $id, $type);
        }

        return parent::renderBlock($block);
    }

    /**
     * MEDIA-4: the one bounded integration point that turns every
     * canonical `{media_id}` prop into a real URL before any renderer
     * (module or standard) sees it, so individual renderers never perform
     * their own media lookup. Recurses automatically: every nested/child
     * block (including slider slides) re-enters renderBlock() and is
     * resolved here too. Block types/props CustomerMediaResolver doesn't
     * know about pass through completely untouched.
     */
    private function resolveMediaProps(array $block, string $type): array
    {
        $key = isset($block['props']) ? 'props' : (isset($block['data']) ? 'data' : null);
        if ($key === null || !is_array($block[$key])) {
            return $block;
        }
        $this->mediaResolver ??= app(CustomerMediaResolver::class);
        $block[$key] = $this->mediaResolver->resolveBlockProps($type, $block[$key]);

        return $block;
    }

    /**
     * TASK 3/3 Phase 6.5C (BLD-28) — the same bounded integration point as
     * resolveMediaProps() above, for internal-page-capable link props
     * (button/CTA/hero action URLs). See InternalLinkResolver's own
     * docblock.
     */
    private function resolveInternalLinkProps(array $block, string $type): array
    {
        $key = isset($block['props']) ? 'props' : (isset($block['data']) ? 'data' : null);
        if ($key === null || !is_array($block[$key])) {
            return $block;
        }
        $this->linkResolver ??= app(InternalLinkResolver::class);
        $block[$key] = $this->linkResolver->resolveBlockProps($type, $block[$key]);

        return $block;
    }

    // ── Module block dispatcher ───────────────────────────────────────────────

    protected function renderModuleBlock(string $type, array $props, string $id): string
    {
        return match (true) {
            in_array($type, ['module:blog_posts', 'module:blog_featured'], true)
                => $this->renderModuleBlogPosts($props, $id),

            $type === 'module:blog_categories'
                => $this->renderModuleBlogCategories($props, $id),

            in_array($type, ['module:shop_product_grid', 'module:products', 'module:product_grid'], true)
                => $this->renderModuleProductGrid($props, $id),

            $type === 'module:shop_product_slider'
                => $this->renderModuleProductSlider($props, $id),

            $type === 'module:shop_categories'
                => $this->renderModuleShopCategories($props, $id),

            $type === 'module:shop_cart_button'
                => $this->renderModuleShopCartButton($props, $id),

            $type === 'module:shop_wishlist_icon'
                => $this->renderModuleShopWishlistIcon($props, $id),

            $type === 'module:loyalty_points_badge'
                => $this->renderModuleLoyaltyPointsBadge($props, $id),

            $type === 'module:installment_badge'
                => $this->renderModuleInstallmentBadge($props, $id),

            $type === 'module:courses_grid'
                => $this->renderModuleCoursesGrid($props, $id),

            $type === 'module:course_enroll_cta'
                => $this->renderModuleCourseEnrollCta($props, $id),

            $type === 'module:my_learning_link'
                => $this->renderModuleMyLearningLink($props, $id),

            $type === 'module:pricing_table'
                => $this->renderModulePricingTable($props, $id),

            $type === 'module:plan_cta'
                => $this->renderModulePlanCta($props, $id),

            $type === 'module:subscriber_stats'
                => $this->renderModuleSubscriberStats($props, $id),

            $type === 'module:contact_quick'
                => $this->renderModuleContactQuick($props, $id),

            $type === 'module:faq_dynamic'
                => $this->renderModuleFaqDynamic($props, $id),

            $type === 'module:support_button'
                => $this->renderModuleSupportButton($props, $id),

            $type === 'module:social_links_bar'
                => $this->renderModuleSocialLinksBar($props, $id),

            $type === 'module:notifications_bar'
                => $this->renderModuleNotificationsBar($props, $id),

            $type === 'module:edu_article_grid'
                => $this->renderModuleEduArticleGrid($props, $id),

            $type === 'module:referral_widget'
                => $this->renderModuleReferralWidget($props, $id),

            $type === 'module:hero_video'
                => $this->renderModuleHeroVideo($props, $id),

            $type === 'module:whatsapp_widget'
                => $this->renderModuleWhatsappWidget($props, $id),

            $type === 'module:form_embed'
                => $this->renderModuleFormEmbed($props, $id),

            $type === 'module:reviews_grid'
                => $this->renderModuleReviewsGrid($props, $id),

            $type === 'module:motion'
                => $this->renderModuleMotion($props, $id),

            // Builder integrity audit (2026-09-11): these 8 were registered
            // canonically but had no dispatch branch here, silently
            // rendering '' regardless of state. Each is now backed by a
            // real host model/route; module:portfolio_grid and
            // module:services_section were de-exposed instead (no backend
            // model — see BlockRegistry::all()).
            $type === 'module:account_quick_links'
                => $this->renderModuleAccountQuickLinks($props, $id),

            $type === 'module:advertisement_zone'
                => $this->renderModuleAdvertisementZone($props, $id),

            $type === 'module:business_card_embed'
                => $this->renderModuleBusinessCardEmbed($props, $id),

            $type === 'module:content_cards'
                => $this->renderModuleContentCards($props, $id),

            $type === 'module:invoice_quick_pay'
                => $this->renderModuleInvoiceQuickPay($props, $id),

            $type === 'module:shop_countdown'
                => $this->renderModuleShopCountdown($props, $id),

            $type === 'module:shop_search_box'
                => $this->renderModuleShopSearchBox($props, $id),

            $type === 'module:site_search'
                => $this->renderModuleSiteSearch($props, $id),

            default => $this->renderModuleGenericPlaceholder($type, $props, $id),
        };
    }

    // ── Hero Video ───────────────────────────────────────────────────────────

    protected function renderModuleHeroVideo(array $props, string $id): string
    {
        $videoId = (int) ($props['video_id'] ?? 0);
        if ($videoId > 0) {
            $video = \App\Models\HomepageVideo::find($videoId);
        } else {
            $video = \App\Models\HomepageVideo::active()->first();
        }

        if (!$video) {
            return "<div id=\"tb-{$id}\" class=\"tb-module-hero-video tb-empty-state\">
                <p class=\"tb-text\">ویدیویی برای نمایش یافت نشد.</p>
            </div>";
        }

        $height = $this->escape($props['height'] ?? '65vh');
        return "<div id=\"tb-{$id}\">"
            . view('components.video-player', ['video' => $video, 'height' => $height])->render()
            . "</div>";
    }

    // ── Video (bare type — real dispatch, no module gate) ──────────────────────
    //
    // Builder integrity audit (2026-09-11): registered since before MEDIA-4,
    // insertable, never had a renderer anywhere in the class hierarchy —
    // always rendered empty. `url`/`poster` resolve through the same
    // CustomerMediaResolver path every other media-capable prop uses
    // (resolveMediaProps() already ran before this method is reached), so
    // this method never performs its own media lookup.

    protected function renderVideo(array $props, string $inner, string $id): string
    {
        $url = trim((string) ($props['url'] ?? ''));
        if ($url === '') {
            return "<div id=\"tb-{$id}\" class=\"tb-video tb-empty-state\"><p class=\"tb-text\">ویدیویی انتخاب نشده است.</p></div>";
        }

        $poster    = trim((string) ($props['poster'] ?? ''));
        $caption   = $this->escape($props['caption'] ?? '');
        $autoplay  = (bool) ($props['autoplay'] ?? false);
        $loop      = (bool) ($props['loop'] ?? false);
        $controls  = $props['controls'] ?? true;

        $attrs = [];
        if ($controls) $attrs[] = 'controls';
        if ($autoplay) $attrs[] = 'autoplay muted playsinline';
        if ($loop) $attrs[] = 'loop';
        $attrStr = implode(' ', $attrs);
        $posterAttr = $poster !== '' ? ' poster="' . $this->escapeAttr($poster) . '"' : '';

        $captionHtml = $caption ? "<figcaption class=\"tb-video-caption\">{$caption}</figcaption>" : '';

        return "<figure id=\"tb-{$id}\" class=\"tb-video\">
            <video src=\"" . $this->escapeAttr($url) . "\"{$posterAttr} {$attrStr} style=\"width:100%;border-radius:0.5rem;display:block;\"></video>
            {$captionHtml}
        </figure>";
    }

    /**
     * BLD-09 / MOD-04 (Full Product Commercial Audit, TASK 1/3):
     * `language_switcher` was registered with a real schema and no
     * renderer anywhere — always empty. A real, working implementation
     * already exists (`<x-language-switcher>`, backed by the real
     * `SetLocale` middleware and the named `lang.switch` route) but was
     * only ever hardcoded into `themes/default/layout.blade.php` — no
     * site owner could place, move, or remove it without editing the
     * theme's own Blade file. Reuses that exact canonical component
     * instead of a second implementation; the hardcoded layout inclusion
     * is left in place (default/back-compat behavior for themes that
     * don't opt into the Builder placement), per the "preserve any
     * required default Theme behavior" scope for this fix.
     */
    protected function renderLanguageSwitcher(array $props, string $inner, string $id): string
    {
        return "<div id=\"tb-{$id}\" class=\"tb-language-switcher\">" . view('components.language-switcher')->render() . "</div>";
    }

    /**
     * BLD-06 (Full Product Commercial Audit, TASK 1/3): `add_to_cart`
     * (product_id, text) was registered with a real schema and no renderer
     * anywhere in the class hierarchy — always empty regardless of props.
     * A real, standalone "Add to Cart" button for one specific product is a
     * genuine, commercially useful capability (matching WooCommerce's own
     * Add-to-Cart button widget) distinct from `cart_icon`/
     * `module:shop_cart_button` (which show the cart's current contents,
     * not add a specific product to it). Posts through the exact same
     * `ShopController::addToCart()` endpoint and session-cart contract
     * every other add-to-cart action in this app already uses.
     */
    protected function renderAddToCart(array $props, string $inner, string $id): string
    {
        if (!SiteHelper::shopEnabled()) {
            return '';
        }

        $productId = (int) ($props['product_id'] ?? 0);
        $text = $this->escape($props['text'] ?? 'افزودن به سبد خرید');

        if ($productId <= 0) {
            return "<div id=\"tb-{$id}\" class=\"tb-add-to-cart tb-empty-state\"><p class=\"tb-text\">محصولی انتخاب نشده است.</p></div>";
        }

        $product = Product::query()->active()->find($productId);
        if (!$product) {
            return "<div id=\"tb-{$id}\" class=\"tb-add-to-cart tb-empty-state\"><p class=\"tb-text\">محصول یافت نشد.</p></div>";
        }

        // A product with real variants (size/color/...) cannot be safely
        // added with a single click and no selection — ShopController::
        // addToCart() itself rejects that (`لطفاً تنوع کالا را انتخاب
        // کنید`). Link to the real product page instead of rendering a
        // button that would always fail server-side.
        if ($product->usesVariants()) {
            $url = route('shop.show', $product->slug);
            return "<a id=\"tb-{$id}\" href=\"{$url}\" class=\"tb-btn tb-btn-primary tb-add-to-cart-link\">{$text}</a>";
        }

        $action = e(route('shop.cart.add'));
        $csrf = csrf_field();
        $productIdAttr = $this->escapeAttr((string) $product->id);

        return "<form id=\"tb-{$id}\" class=\"tb-add-to-cart\" method=\"post\" action=\"{$action}\">
            {$csrf}
            <input type=\"hidden\" name=\"product_id\" value=\"{$productIdAttr}\">
            <input type=\"hidden\" name=\"quantity\" value=\"1\">
            <button type=\"submit\" class=\"tb-btn tb-btn-primary\">{$text}</button>
        </form>";
    }

    /**
     * Placeholder re-evaluation, `price_box` (Full Product Commercial
     * Audit, TASK 2A): the canonical renderer always showed "price will
     * display once connected to the shop" regardless of `product_id` —
     * never actually resolved a real price even when one was selected.
     * Real WooCommerce-style price display: current price, and the
     * original price struck through when the product is on sale — using
     * the same `effective_price`/`sale_price` contract `renderAddToCart()`
     * and the shop pages already use, not a new price calculation.
     */
    protected function renderPriceBox(array $props, string $inner, string $id): string
    {
        if (!SiteHelper::shopEnabled()) {
            return '';
        }

        $productId = (int) ($props['product_id'] ?? 0);
        if ($productId <= 0) {
            return "<div id=\"tb-{$id}\" class=\"tb-price-box tb-empty-state\"><p class=\"tb-text\">محصولی انتخاب نشده است.</p></div>";
        }

        $product = Product::query()->active()->find($productId);
        if (!$product) {
            return "<div id=\"tb-{$id}\" class=\"tb-price-box tb-empty-state\"><p class=\"tb-text\">محصول یافت نشد.</p></div>";
        }

        $unit = CurrencyHelper::unitLabel();
        $current = CurrencyHelper::formatPrice($product->effective_price) . ' ' . $unit;
        $onSale = $product->sale_price !== null && (float) $product->sale_price < (float) $product->price;

        if ($onSale) {
            $original = CurrencyHelper::formatPrice($product->price) . ' ' . $unit;
            return "<div id=\"tb-{$id}\" class=\"tb-price-box\">
                <span class=\"tb-price-box-original\" style=\"text-decoration:line-through;color:#9ca3af;font-size:0.9em;margin-inline-end:0.5rem;\">{$original}</span>
                <span class=\"tb-price-box-current\" style=\"color:#dc2626;font-weight:700;\">{$current}</span>
            </div>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-price-box\"><span class=\"tb-price-box-current\" style=\"font-weight:700;\">{$current}</span></div>";
    }

    /**
     * Builder integrity audit (2026-09-11): customer-site's local
     * 'social_icons' schema (BlockRegistry::all()) declares only a
     * 'style' select — no 'links' field, unlike the canonical shared
     * schema this type would otherwise inherit the renderer from. The
     * canonical renderSocialIcons() reads $props['links'] (never present
     * here) and ignores 'style' entirely — real data could never reach
     * it. This host override reads the same real SocialLink model
     * module:social_links_bar already uses and honors all 3 real style
     * variants, matching the local schema's actual intent instead of the
     * canonical one it silently inherited from.
     *
     * BLD-11..13 (Full Product Commercial Audit, TASK 2A-CONTINUATION): an
     * explicit `links` prop now wins over the global model — this override
     * previously ignored it unconditionally, so real per-block authored
     * data (e.g. `nav_footer.social_links`, now a real authored repeater —
     * see renderNavFooter()'s `renderSocialIcons(['links' => $socialLinks],
     * ...)` call) could never actually reach the render, a duplicate-
     * authority gap the audit forbids. The site-wide SocialLink model
     * remains the fallback only when no explicit links were supplied (the
     * standalone `social_icons` block's own schema truly has none, per the
     * original docblock above).
     */
    protected function renderSocialIcons(array $props, string $inner, string $id): string
    {
        $links = (is_array($props['links'] ?? null) && !empty($props['links'])) ? $props['links'] : [];

        if (empty($links) && class_exists(\App\Models\SocialLink::class)) {
            $links = \App\Models\SocialLink::orderBy('order')->get()
                ->map(fn ($l) => ['url' => $l->url, 'label' => $l->title ?? $l->platform ?? ''])->toArray();
        }
        if (empty($links)) {
            return '';
        }

        $requestedStyle = $props['style'] ?? 'icons';
        $style = in_array($requestedStyle, ['buttons', 'text'], true) ? $requestedStyle : 'icons';
        $items = [];
        foreach ($links as $link) {
            $url   = $this->escapeAttr($link['url'] ?? '#');
            $label = $this->escape($link['label'] ?? '');
            $shown = match ($style) {
                'icons' => mb_substr($label, 0, 1),
                default => $label,
            };
            $cls = $style === 'buttons' ? 'tb-btn tb-btn-secondary' : 'tb-social-link';
            $items[] = "<a href=\"{$url}\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"{$cls}\" aria-label=\"{$label}\">{$shown}</a>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-social-icons tb-social-icons-{$style}\" style=\"display:flex;gap:0.5rem;flex-wrap:wrap;\">" . implode('', $items) . "</div>";
    }

    // ── Blog ─────────────────────────────────────────────────────────────────

    protected function renderModuleBlogPosts(array $props, string $id): string
    {
        if (!SiteHelper::blogEnabled()) {
            return '';
        }

        $title        = $this->escape($props['title'] ?? 'آخرین مطالب');
        $limit        = max(1, (int) ($props['limit'] ?? 6));
        $layout       = $props['layout'] ?? 'grid';
        $showExcerpt  = $props['show_excerpt'] ?? true;
        $showDate     = $props['show_date'] ?? true;
        $categorySlug = trim((string) ($props['category_slug'] ?? ''));

        $posts = Post::published()
            ->when($categorySlug !== '', fn ($q) => $q->whereHas('category', fn ($qc) => $qc->where('slug', $categorySlug)))
            ->latest('published_at')->take($limit)->get();

        if ($posts->isEmpty()) {
            return "<div id=\"tb-{$id}\" class=\"tb-module-blog tb-empty-state\">
                <p class=\"tb-text\">هنوز مطلبی منتشر نشده است.</p>
                <a href=\"" . e(route('blog.index')) . "\" class=\"tb-btn tb-btn-secondary\">مشاهده وبلاگ</a>
            </div>";
        }

        $colsClass = $layout === 'list' ? 'tb-grid-cols-1' : 'tb-grid-cols-3';
        $cards = [];
        foreach ($posts as $post) {
            $url        = route('blog.show', $post->slug);
            $t          = $this->escape($post->title);
            $excerpt    = $this->escape(Str::limit(strip_tags($post->excerpt ?? $post->body ?? ''), 110));
            $date       = $post->published_at ? Jalali::formatFa($post->published_at, 'Y/m/d') : '';
            $imgHtml    = !empty($post->featured_image)
                ? "<div class=\"tb-blog-card-img\"><img src=\"" . e(ImageHelper::url($post->featured_image)) . "\" alt=\"{$t}\" loading=\"lazy\"></div>"
                : "<div class=\"tb-blog-card-img tb-blog-card-noimg\">📰</div>";
            $dateHtml    = $showDate ? "<p class=\"tb-blog-card-meta\">{$date}</p>" : '';
            $excerptHtml = $showExcerpt ? "<p class=\"tb-blog-card-excerpt\">{$excerpt}</p>" : '';
            $cards[] = "<a href=\"{$url}\" class=\"tb-blog-card\">{$imgHtml}<div class=\"tb-blog-card-body\">
                <h3 class=\"tb-blog-card-title\">{$t}</h3>
                {$dateHtml}
                {$excerptHtml}
            </div></a>";
        }

        $titleHtml = $title ? "<h2 class=\"tb-heading tb-section-title\">{$title}</h2>" : '';
        return "<div id=\"tb-{$id}\" class=\"tb-module-blog\">
            {$titleHtml}
            <div class=\"tb-grid {$colsClass} tb-gap-lg tb-blog-grid\">" . implode('', $cards) . "</div>
            <div style=\"text-align:center;margin-top:1.5rem;\">
                <a href=\"" . e(route('blog.index')) . "\" class=\"tb-btn tb-btn-secondary\">مشاهده همه مطالب</a>
            </div>
        </div>";
    }

    /**
     * BLD-02 (Full Product Commercial Audit, TASK 1/3): `module:blog_categories`
     * used to be dispatched into renderModuleBlogPosts(), which queries
     * `Post`, never `Category`, and ignores this type's own `style` field
     * entirely — a schema/renderer contract mismatch, not a real taxonomy
     * widget. This queries real categories with real post counts.
     */
    protected function renderModuleBlogCategories(array $props, string $id): string
    {
        if (!SiteHelper::blogEnabled()) {
            return '';
        }

        $title = $this->escape($props['title'] ?? 'دسته‌بندی‌ها');
        $requestedStyle = $props['style'] ?? 'cards';
        $style = in_array($requestedStyle, ['cards', 'list', 'badges'], true) ? $requestedStyle : 'cards';
        $limit = max(1, (int) ($props['limit'] ?? 8));

        $categories = \App\Models\Category::withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('order')
            ->take($limit)
            ->get()
            ->filter(fn ($c) => $c->posts_count > 0)
            ->values();

        $titleHtml = $title ? "<h2 class=\"tb-heading tb-section-title\">{$title}</h2>" : '';

        if ($categories->isEmpty()) {
            return "<div id=\"tb-{$id}\" class=\"tb-module-blog-categories tb-empty-state\">
                {$titleHtml}
                <p class=\"tb-text\">هنوز دسته‌بندی‌ای با مطلب منتشرشده وجود ندارد.</p>
            </div>";
        }

        $items = [];
        foreach ($categories as $category) {
            $url = route('blog.category', $category->slug);
            $name = $this->escape($category->name);
            $count = (int) $category->posts_count;
            $items[] = match ($style) {
                'list' => "<li><a href=\"{$url}\" class=\"tb-blog-category-list-item\">{$name} <span class=\"tb-blog-category-count\">({$count})</span></a></li>",
                'badges' => "<a href=\"{$url}\" class=\"tb-blog-category-badge\">{$name} <span class=\"tb-blog-category-count\">{$count}</span></a>",
                default => "<a href=\"{$url}\" class=\"tb-blog-category-card\"><span class=\"tb-blog-category-name\">{$name}</span><span class=\"tb-blog-category-count\">{$count} مطلب</span></a>",
            };
        }

        $wrapClass = match ($style) {
            'list' => 'tb-blog-category-list',
            'badges' => 'tb-blog-category-badges',
            default => 'tb-blog-category-cards',
        };
        $wrapTag = $style === 'list' ? 'ul' : 'div';
        $inner = implode('', $items);

        return "<div id=\"tb-{$id}\" class=\"tb-module-blog-categories\">
            {$titleHtml}
            <{$wrapTag} class=\"{$wrapClass}\">{$inner}</{$wrapTag}>
        </div>";
    }

    // ── Shop ─────────────────────────────────────────────────────────────────

    protected function renderModuleProductGrid(array $props, string $id): string
    {
        if (!SiteHelper::shopEnabled()) {
            return '';
        }

        $title        = $this->escape($props['title'] ?? 'محصولات');
        $limit        = max(1, (int) ($props['limit'] ?? 8));
        $columns      = max(2, min(4, (int) ($props['columns'] ?? 4)));
        $source       = $props['source'] ?? 'recent';
        $categorySlug = trim((string) ($props['category_slug'] ?? ''));

        // Builder integrity audit (2026-09-11): 'featured' previously
        // queried `is_featured`, a column that has never existed on
        // `products` (confirmed: no migration, no model field) — every
        // real render with source=featured (the canonical DEFAULT for
        // module:shop_product_slider) crashed with a real SQL error,
        // silently swallowed by renderBlock()'s catch (that catch's own
        // missing log entry was fixed alongside this). `is_new` is a real,
        // existing column with the closest honest semantic match.
        $query = Product::query()->active();
        if ($source === 'featured') {
            $query->where('is_new', true);
        }
        if ($categorySlug !== '') {
            $query->whereHas('category', fn ($qc) => $qc->where('slug', $categorySlug));
        }
        $products = $query->latest()->take($limit)->get();

        $titleHtml = $title ? "<h2 class=\"tb-heading tb-section-title\">{$title}</h2>" : '';

        if ($products->isEmpty()) {
            return "<div id=\"tb-{$id}\" class=\"tb-module-products\">
                {$titleHtml}
                <div class=\"tb-product-grid-empty\">
                    <p>هنوز محصولی ثبت نشده است.</p>
                    <a href=\"" . e(route('shop.index')) . "\" class=\"tb-btn tb-btn-primary\">مشاهده فروشگاه</a>
                </div>
            </div>";
        }

        $colsClass = "tb-grid-cols-{$columns}";
        $cards = [];
        foreach ($products as $product) {
            $url     = route('shop.show', $product->slug);
            $t       = $this->escape($product->title);
            $price   = CurrencyHelper::formatPrice($product->effective_price) . ' ' . CurrencyHelper::unitLabel();
            $imgHtml = $product->image
                ? "<div class=\"tb-product-card-img\"><img src=\"" . e(ImageHelper::url($product->image)) . "\" alt=\"{$t}\" loading=\"lazy\"></div>"
                : "<div class=\"tb-product-card-img tb-product-card-noimg\">📦</div>";
            $cards[] = "<a href=\"{$url}\" class=\"tb-product-card\">{$imgHtml}
                <div class=\"tb-product-card-inner\">
                    <h3 class=\"tb-product-title\">{$t}</h3>
                    <div class=\"tb-product-price\">{$price}</div>
                </div>
            </a>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-module-products\">
            {$titleHtml}
            <div class=\"tb-grid {$colsClass} tb-gap-md tb-section-products\">" . implode('', $cards) . "</div>
            <div style=\"text-align:center;margin-top:1.5rem;\">
                <a href=\"" . e(route('shop.index')) . "\" class=\"tb-btn tb-btn-secondary\">مشاهده فروشگاه</a>
            </div>
        </div>";
    }

    /**
     * BLD-03 (Full Product Commercial Audit, TASK 1/3): `module:shop_product_slider`
     * used to be dispatched into renderModuleProductGrid() alongside the
     * plain grid type — its `autoplay` field had no effect and it rendered
     * a static grid, not a slider, despite its own name/schema. Reuses the
     * grid's real source-filtering query (including the documented
     * featured→is_new mapping) but outputs through the existing
     * renderCarousel() runtime, matching the real autoplay/interval props.
     */
    protected function renderModuleProductSlider(array $props, string $id): string
    {
        if (!SiteHelper::shopEnabled()) {
            return '';
        }

        $title = $this->escape($props['title'] ?? 'محصولات پیشنهادی');
        $limit = max(1, (int) ($props['limit'] ?? 6));
        $source = $props['source'] ?? 'featured';

        $query = Product::query()->active();
        if ($source === 'featured') {
            $query->where('is_new', true);
        }
        $products = $query->latest()->take($limit)->get();

        $titleHtml = $title ? "<h2 class=\"tb-heading tb-section-title\">{$title}</h2>" : '';

        if ($products->isEmpty()) {
            return "<div id=\"tb-{$id}\" class=\"tb-module-products\">{$titleHtml}</div>";
        }

        $slidesHtml = [];
        foreach ($products as $product) {
            $url   = route('shop.show', $product->slug);
            $t     = $this->escape($product->title);
            $price = CurrencyHelper::formatPrice($product->effective_price) . ' ' . CurrencyHelper::unitLabel();
            $imgHtml = $product->image
                ? "<div class=\"tb-product-card-img\"><img src=\"" . e(ImageHelper::url($product->image)) . "\" alt=\"{$t}\" loading=\"lazy\"></div>"
                : "<div class=\"tb-product-card-img tb-product-card-noimg\">📦</div>";
            $slidesHtml[] = "<a href=\"{$url}\" class=\"tb-product-card\" style=\"max-width:20rem;margin:0 auto;display:block;\">{$imgHtml}
                <div class=\"tb-product-card-inner\">
                    <h3 class=\"tb-product-title\">{$t}</h3>
                    <div class=\"tb-product-price\">{$price}</div>
                </div>
            </a>";
        }

        $carousel = $this->renderCarousel(
            $slidesHtml,
            $id . '-track',
            (bool) ($props['autoplay'] ?? true),
            (int) ($props['interval'] ?? 5000),
            'tb-product-slider'
        );

        return "<div id=\"tb-{$id}\" class=\"tb-module-products\">{$titleHtml}{$carousel}</div>";
    }

    protected function renderModuleShopCategories(array $props, string $id): string
    {
        if (!SiteHelper::shopEnabled()) {
            return '';
        }
        $title      = $this->escape($props['title'] ?? 'دسته‌بندی‌ها');
        $requestedStyle = $props['style'] ?? 'grid';
        $style      = in_array($requestedStyle, ['list', 'horizontal'], true) ? $requestedStyle : 'grid';
        $limit      = max(1, (int) ($props['limit'] ?? 6));
        $showCount  = $props['show_count'] ?? true;

        $categories = \App\Models\ProductCategory::query()
            ->when($showCount, fn ($q) => $q->withCount('products'))
            ->orderBy('order')->take($limit)->get();

        $titleHtml = $title ? "<h2 class=\"tb-heading tb-section-title\">{$title}</h2>" : '';
        if ($categories->isEmpty()) {
            return "<div id=\"tb-{$id}\" class=\"tb-module-shop-categories\">
                {$titleHtml}
                <div style=\"text-align:center;\"><a href=\"" . e(route('shop.index')) . "\" class=\"tb-btn tb-btn-secondary\">مشاهده فروشگاه</a></div>
            </div>";
        }

        $wrapStyle = $style === 'horizontal'
            ? 'display:flex;gap:0.75rem;flex-wrap:wrap;'
            : ($style === 'list' ? 'display:flex;flex-direction:column;gap:0.5rem;' : 'display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:1rem;');

        $cards = [];
        foreach ($categories as $cat) {
            $name  = $this->escape($cat->name);
            $url   = e(route('shop.index', ['category' => $cat->slug]));
            $count = $showCount ? "<span style=\"color:#6b7280;font-size:0.8rem;\">(" . (int) ($cat->products_count ?? 0) . ")</span>" : '';
            $cards[] = "<a href=\"{$url}\" class=\"tb-shop-category-link\" style=\"display:flex;align-items:center;justify-content:space-between;gap:0.5rem;padding:0.65rem 1rem;border:1px solid #e5e7eb;border-radius:0.5rem;text-decoration:none;color:inherit;\">{$name}{$count}</a>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-module-shop-categories tb-module-shop-categories-{$style}\">
            {$titleHtml}
            <div style=\"{$wrapStyle}\">" . implode('', $cards) . "</div>
        </div>";
    }

    protected function renderModuleShopCartButton(array $props, string $id): string
    {
        if (!SiteHelper::shopEnabled()) {
            return '';
        }
        $label   = $this->escape($props['label'] ?? 'سبد خرید');
        $requestedStyle = $props['style'] ?? 'button';
        $style   = in_array($requestedStyle, ['icon', 'full'], true) ? $requestedStyle : 'button';
        $cartUrl = route('shop.cart');
        $count   = 0;
        $total   = 0;
        foreach (session('cart', []) as $item) {
            $count += (int) ($item['quantity'] ?? 0);
            $total += (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 0);
        }
        $badge = $count > 0
            ? "<span style=\"background:#ef4444;color:#fff;font-size:0.7rem;padding:0.1rem 0.4rem;border-radius:999px;\">{$count}</span>"
            : '';

        if ($style === 'icon') {
            return "<a id=\"tb-{$id}\" href=\"{$cartUrl}\" class=\"tb-cart-link tb-cart-link-icon\" aria-label=\"{$label}\">🛒{$badge}</a>";
        }
        if ($style === 'full') {
            $priceHtml = $total > 0 ? " — " . CurrencyHelper::formatPrice($total) . ' ' . CurrencyHelper::unitLabel() : '';
            return "<a id=\"tb-{$id}\" href=\"{$cartUrl}\" class=\"tb-cart-link tb-cart-link-full\">🛒 {$label}{$priceHtml} {$badge}</a>";
        }
        return "<a id=\"tb-{$id}\" href=\"{$cartUrl}\" class=\"tb-cart-link\" aria-label=\"{$label}\">🛒 {$label} {$badge}</a>";
    }

    /**
     * MOD-01 (Full Product Commercial Audit, TASK 2A): reuses the real,
     * already-working `ShopWishlistService` (dual guest-session/auth-user
     * identity, exactly like the cart) for wishlist mode, and the real
     * `session('shop_compare')` list for compare mode — no new backend
     * logic, same pattern as renderModuleShopCartButton() above.
     */
    protected function renderModuleShopWishlistIcon(array $props, string $id): string
    {
        if (!SiteHelper::shopEnabled()) {
            return '';
        }
        $mode = ($props['mode'] ?? 'wishlist') === 'compare' ? 'compare' : 'wishlist';
        $requestedStyle = $props['style'] ?? 'icon';
        $style = in_array($requestedStyle, ['icon', 'full'], true) ? $requestedStyle : 'icon';

        if ($mode === 'compare') {
            $label = $this->escape($props['label'] ?? 'مقایسه');
            $url = route('shop.compare');
            $count = count(array_unique(array_map('intval', (array) session('shop_compare', []))));
            $icon = '⚖️';
        } else {
            $label = $this->escape($props['label'] ?? 'علاقه‌مندی‌ها');
            $url = route('shop.wishlist');
            $count = app(\App\Services\ShopWishlistService::class)->wishlist()->items()->count();
            $icon = '❤️';
        }

        $badge = $count > 0
            ? "<span style=\"background:#ef4444;color:#fff;font-size:0.7rem;padding:0.1rem 0.4rem;border-radius:999px;\">{$count}</span>"
            : '';

        if ($style === 'icon') {
            return "<a id=\"tb-{$id}\" href=\"{$url}\" class=\"tb-wishlist-link tb-wishlist-link-icon\" aria-label=\"{$label}\">{$icon}{$badge}</a>";
        }
        return "<a id=\"tb-{$id}\" href=\"{$url}\" class=\"tb-wishlist-link\" aria-label=\"{$label}\">{$icon} {$label} {$badge}</a>";
    }

    /**
     * MOD-02 (Full Product Commercial Audit, TASK 2A-CONTINUATION): reuses
     * the real, already-working InstallmentService/InstallmentPlan backend
     * (eligiblePlans()/calculate(), the same code the public installment
     * flow itself uses — InstallmentController::plans()/apply()) — no new
     * rate/amount/eligibility calculation invented here. `product_id` is a
     * real author-selected product, same contract as renderAddToCart()/
     * renderPriceBox() above (an empty-state message, never a fabricated
     * price, when unselected or the product can't be found). When the
     * product's current price has no eligible active plan at all (no plans
     * configured, or the amount falls outside every plan's min/max range),
     * this renders a real "not available for this product" empty state
     * rather than an empty/broken badge.
     */
    protected function renderModuleInstallmentBadge(array $props, string $id): string
    {
        if (!SiteHelper::shopEnabled()) {
            return '';
        }

        $productId = (int) ($props['product_id'] ?? 0);
        if ($productId <= 0) {
            return "<div id=\"tb-{$id}\" class=\"tb-installment-badge tb-empty-state\"><p class=\"tb-text\">محصولی انتخاب نشده است.</p></div>";
        }

        $product = Product::query()->active()->find($productId);
        if (!$product) {
            return "<div id=\"tb-{$id}\" class=\"tb-installment-badge tb-empty-state\"><p class=\"tb-text\">محصول یافت نشد.</p></div>";
        }

        $amount = (int) $product->effective_price;
        $plans = app(\App\Services\InstallmentService::class)->eligiblePlans($amount);

        if ($plans->isEmpty()) {
            return "<div id=\"tb-{$id}\" class=\"tb-installment-badge tb-empty-state\"><p class=\"tb-text\">خرید اقساطی برای این محصول در دسترس نیست.</p></div>";
        }

        $applyUrl = route('installments.apply', ['product_id' => $product->id]);
        $requestedStyle = $props['style'] ?? 'badge';
        $style = in_array($requestedStyle, ['badge', 'full'], true) ? $requestedStyle : 'badge';
        $unit = CurrencyHelper::unitLabel();

        if ($style === 'badge') {
            $cheapest = $plans->sortBy(fn ($p) => $p->calculate($amount)['monthly_payment'])->first();
            $monthly = CurrencyHelper::formatPrice($cheapest->calculate($amount)['monthly_payment']) . ' ' . $unit;
            return "<a id=\"tb-{$id}\" href=\"" . e($applyUrl) . "\" class=\"tb-installment-badge tb-installment-badge-compact\">"
                . "<span class=\"tb-installment-badge-icon\">💳</span>"
                . "<span>خرید اقساطی از {$monthly}/ماه</span></a>";
        }

        $rows = '';
        foreach ($plans as $plan) {
            $calc = $plan->calculate($amount);
            $name = $this->escape($plan->name);
            $months = (int) $calc['months'];
            $monthly = CurrencyHelper::formatPrice($calc['monthly_payment']) . ' ' . $unit;
            $down = CurrencyHelper::formatPrice($calc['down_payment']) . ' ' . $unit;
            $rows .= "<div class=\"tb-installment-plan-row\">"
                . "<span class=\"tb-installment-plan-name\">{$name}</span>"
                . "<span class=\"tb-installment-plan-detail\">{$months} قسط {$monthly} (پیش‌پرداخت {$down})</span></div>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-installment-badge tb-installment-badge-full\">"
            . "<div class=\"tb-installment-plans-list\">{$rows}</div>"
            . "<a href=\"" . e($applyUrl) . "\" class=\"tb-btn tb-btn-secondary\">مشاهده و ثبت خرید اقساطی</a></div>";
    }

    /**
     * MOD-03: real balance via MembershipPointsService::getBalance(), same
     * icon/badge/link shape as renderModuleShopWishlistIcon(). Points are
     * tied to a real authenticated user_id (never a guest session), so a
     * guest sees nothing here — never a fabricated zero balance.
     */
    protected function renderModuleLoyaltyPointsBadge(array $props, string $id): string
    {
        $user = auth()->user();
        if (!$user) {
            return '';
        }

        $requestedStyle = $props['style'] ?? 'icon';
        $style = in_array($requestedStyle, ['icon', 'full'], true) ? $requestedStyle : 'icon';
        $label = $this->escape($props['label'] ?? 'امتیاز من');
        $url = route('account.points.index');
        $balance = app(\App\Services\MembershipPointsService::class)->getBalance($user);
        $icon = '⭐';

        $badge = $balance > 0
            ? "<span style=\"background:#f59e0b;color:#fff;font-size:0.7rem;padding:0.1rem 0.4rem;border-radius:999px;\">{$balance}</span>"
            : '';

        if ($style === 'icon') {
            return "<a id=\"tb-{$id}\" href=\"{$url}\" class=\"tb-loyalty-points-link tb-loyalty-points-link-icon\" aria-label=\"{$label}\">{$icon}{$badge}</a>";
        }
        return "<a id=\"tb-{$id}\" href=\"{$url}\" class=\"tb-loyalty-points-link\" aria-label=\"{$label}\">{$icon} {$label} {$badge}</a>";
    }

    // ── Courses ───────────────────────────────────────────────────────────────

    protected function renderModuleCoursesGrid(array $props, string $id): string
    {
        if (!SiteHelper::coursesEnabled()) {
            return '';
        }

        $title   = $this->escape($props['title'] ?? 'دوره‌های آموزشی');
        $limit   = max(1, (int) ($props['limit'] ?? 6));
        $layout  = ($props['layout'] ?? 'grid') === 'list' ? 'list' : 'grid';
        $columns = max(2, min(4, (int) ($props['columns'] ?? 3)));
        $showPrice = $props['show_price'] ?? true;
        $courses = Course::active()->latest()->take($limit)->get();

        $titleHtml = $title ? "<h2 class=\"tb-heading tb-section-title\">{$title}</h2>" : '';

        if ($courses->isEmpty()) {
            return "<div id=\"tb-{$id}\" class=\"tb-module-courses\">
                {$titleHtml}
                <div class=\"tb-product-grid-empty\">
                    <p>هنوز دوره‌ای ثبت نشده است.</p>
                    <a href=\"" . e(route('courses.index')) . "\" class=\"tb-btn tb-btn-primary\">مشاهده دوره‌ها</a>
                </div>
            </div>";
        }

        $colsClass = $layout === 'list' ? 'tb-grid-cols-1' : "tb-grid-cols-{$columns}";
        $cards = [];
        foreach ($courses as $course) {
            $url     = route('courses.show', $course->slug);
            $t       = $this->escape($course->title);
            $excerpt = $this->escape(Str::limit(strip_tags($course->description ?? ''), 90));
            $price   = $course->price
                ? CurrencyHelper::formatPrice($course->price) . ' ' . CurrencyHelper::unitLabel()
                : 'رایگان';
            $imgHtml = $course->thumbnail
                ? "<div class=\"tb-product-card-img\"><img src=\"" . e(ImageHelper::url($course->thumbnail)) . "\" alt=\"{$t}\" loading=\"lazy\"></div>"
                : "<div class=\"tb-product-card-img tb-product-card-noimg\" style=\"font-size:2rem;\">🎓</div>";
            $priceHtml = $showPrice ? "<div class=\"tb-product-price\">{$price}</div>" : '';
            $cards[] = "<a href=\"{$url}\" class=\"tb-product-card\" style=\"text-align:right;\">
                {$imgHtml}
                <div class=\"tb-product-card-inner\">
                    <h3 class=\"tb-product-title\">{$t}</h3>
                    <p style=\"font-size:0.85rem;color:#6b7280;margin:0.25rem 0;\">{$excerpt}</p>
                    {$priceHtml}
                </div>
            </a>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-module-courses\">
            {$titleHtml}
            <div class=\"tb-grid {$colsClass} tb-gap-md\">" . implode('', $cards) . "</div>
            <div style=\"text-align:center;margin-top:1.5rem;\">
                <a href=\"" . e(route('courses.index')) . "\" class=\"tb-btn tb-btn-secondary\">مشاهده همه دوره‌ها</a>
            </div>
        </div>";
    }

    protected function renderModuleCourseEnrollCta(array $props, string $id): string
    {
        if (!SiteHelper::coursesEnabled()) {
            return '';
        }
        $title    = $this->escape($props['title'] ?? 'همین الان شروع کن!');
        $subtitle = $this->escape($props['subtitle'] ?? 'به هزاران دانشجو بپیوند');
        $btnText  = $this->escape($props['button_text'] ?? 'مشاهده دوره‌ها');
        $bgColor  = $this->escapeAttr($props['bg_color'] ?? '#4f46e5');
        $url      = route('courses.index');

        return "<section id=\"tb-{$id}\" class=\"tb-cta\" style=\"background:{$bgColor}\">
            <h2 class=\"tb-cta-title\">{$title}</h2>
            <p class=\"tb-cta-subtitle\">{$subtitle}</p>
            <a href=\"{$url}\" class=\"tb-btn\" style=\"background:#fff;color:{$bgColor};margin-top:0.5rem;\">{$btnText}</a>
        </section>";
    }

    protected function renderModuleMyLearningLink(array $props, string $id): string
    {
        if (!SiteHelper::coursesEnabled() || !auth()->check()) {
            return '';
        }
        $label = $this->escape($props['label'] ?? 'یادگیری من');
        $url   = route('courses.my-learning');
        $style = $props['style'] ?? 'button';

        if ($style === 'link') {
            return "<a id=\"tb-{$id}\" href=\"{$url}\" class=\"tb-link\" style=\"text-decoration:underline;\">📚 {$label}</a>";
        }
        if ($style === 'card') {
            return "<a id=\"tb-{$id}\" href=\"{$url}\" class=\"tb-module-my-learning-card\" style=\"display:flex;align-items:center;gap:0.75rem;padding:1rem 1.25rem;border:1px solid #e5e7eb;border-radius:0.75rem;text-decoration:none;color:inherit;\">
                <span style=\"font-size:1.5rem;\">📚</span>
                <span style=\"font-weight:700;\">{$label}</span>
            </a>";
        }
        return "<a id=\"tb-{$id}\" href=\"{$url}\" class=\"tb-btn tb-btn-primary\">📚 {$label}</a>";
    }

    // ── Subscription ─────────────────────────────────────────────────────────

    protected function renderModulePricingTable(array $props, string $id): string
    {
        if (!SiteHelper::moduleEnabled('subscription')) {
            return '';
        }
        $title    = $this->escape($props['title'] ?? 'پلن‌های اشتراک');
        $subtitle = $this->escape($props['subtitle'] ?? 'پلنی که مناسب شماست را انتخاب کنید');
        $url      = route('pricing.index');

        return "<div id=\"tb-{$id}\" class=\"tb-module-pricing\">
            <h2 class=\"tb-heading tb-section-title\" style=\"text-align:center;\">{$title}</h2>
            <p class=\"tb-text\" style=\"text-align:center;margin-bottom:1.5rem;\">{$subtitle}</p>
            <div style=\"text-align:center;\">
                <a href=\"{$url}\" class=\"tb-btn tb-btn-primary\" style=\"font-size:1.1rem;padding:0.9rem 2rem;\">مشاهده پلن‌ها و قیمت‌ها</a>
            </div>
        </div>";
    }

    protected function renderModulePlanCta(array $props, string $id): string
    {
        if (!SiteHelper::moduleEnabled('subscription')) {
            return '';
        }
        $title    = $this->escape($props['title'] ?? 'به کاربران ویژه بپیوند');
        $subtitle = $this->escape($props['subtitle'] ?? '');
        $btnText  = $this->escape($props['button_text'] ?? 'مشاهده پلن‌ها');
        $bgColor  = $this->escapeAttr($props['bg_color'] ?? '#7c3aed');
        $url      = route('pricing.index');

        return "<section id=\"tb-{$id}\" class=\"tb-cta\" style=\"background:{$bgColor}\">
            <h2 class=\"tb-cta-title\">{$title}</h2>
            " . ($subtitle ? "<p class=\"tb-cta-subtitle\">{$subtitle}</p>" : '') . "
            <a href=\"{$url}\" class=\"tb-btn\" style=\"background:#fff;color:{$bgColor};margin-top:0.5rem;\">{$btnText}</a>
        </section>";
    }

    protected function renderModuleSubscriberStats(array $props, string $id): string
    {
        if (!SiteHelper::moduleEnabled('subscription')) {
            return '';
        }
        $label     = $this->escape($props['label'] ?? 'کاربر فعال');
        $showCount = $props['show_count'] ?? true;
        $style     = ($props['style'] ?? 'badge') === 'card' ? 'card' : 'badge';

        $countHtml = '';
        if ($showCount && class_exists(\App\Models\Subscription::class)) {
            $count = \App\Models\Subscription::where('status', \App\Models\Subscription::STATUS_ACTIVE)->count();
            $countHtml = "<strong>" . number_format($count) . "</strong> ";
        }

        if ($style === 'card') {
            return "<div id=\"tb-{$id}\" class=\"tb-module-stats tb-module-stats-card\" style=\"text-align:center;padding:1.5rem;border:1px solid #e5e7eb;border-radius:0.75rem;\">
                <div style=\"font-size:1.75rem;color:#4f46e5;\">👥</div>
                <div style=\"font-size:1.25rem;margin-top:0.25rem;\">{$countHtml}{$label}</div>
            </div>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-module-stats tb-module-stats-badge\">
            <div style=\"display:inline-flex;align-items:center;gap:0.5rem;background:#f3f4f6;border-radius:999px;padding:0.4rem 1rem;font-size:0.9rem;\">
                <span style=\"color:#4f46e5;font-size:1rem;\">👥</span>
                <span>{$countHtml}{$label}</span>
            </div>
        </div>";
    }

    // ── Contact ───────────────────────────────────────────────────────────────

    protected function renderModuleContactQuick(array $props, string $id): string
    {
        $title   = $this->escape($props['title'] ?? 'تماس با ما');
        $phone   = \App\Models\Setting::get('phone', '');
        $address = \App\Models\Setting::get('address', '');

        $items = ["<h2 class=\"tb-heading tb-section-title\">{$title}</h2>"];
        if ($phone && ($props['show_phone'] ?? true)) {
            $items[] = "<p class=\"tb-text\"><strong>📞 تلفن:</strong> " . $this->escape($phone) . "</p>";
        }
        if ($address && ($props['show_address'] ?? true)) {
            $items[] = "<p class=\"tb-text\"><strong>📍 آدرس:</strong> " . $this->escape($address) . "</p>";
        }
        if ($address && ($props['show_map'] ?? false)) {
            $mapSrc = 'https://maps.google.com/maps?q=' . urlencode($address) . '&output=embed';
            $items[] = "<div class=\"tb-contact-map\"><iframe src=\"" . $this->escapeAttr($mapSrc) . "\" loading=\"lazy\" style=\"width:100%;height:280px;border:0;\" title=\"نقشه\"></iframe></div>";
        }
        $items[] = "<p class=\"tb-text\"><a href=\"" . e(route('contact.show')) . "\" class=\"tb-btn tb-btn-primary\" style=\"margin-top:0.5rem;\">ارسال پیام</a></p>";

        return "<div id=\"tb-{$id}\" class=\"tb-module-contact\">" . implode('', $items) . "</div>";
    }

    // ── FAQ ───────────────────────────────────────────────────────────────────

    protected function renderModuleFaqDynamic(array $props, string $id): string
    {
        $title = $this->escape($props['title'] ?? 'سوالات متداول');
        $limit = max(1, (int) ($props['limit'] ?? 10));
        $style = ($props['style'] ?? 'accordion') === 'list' ? 'list' : 'accordion';
        $items = array_slice(is_array($props['items'] ?? null) ? $props['items'] : [], 0, $limit);

        if (empty($items)) {
            return "<div id=\"tb-{$id}\" class=\"tb-faq\">
                <h2 class=\"tb-heading tb-section-title\">{$title}</h2>
                <p class=\"tb-text\">سوالات متداول از پنل مدیریت قابل ویرایش است.</p>
            </div>";
        }

        $rows = ["<h2 class=\"tb-heading tb-section-title\">{$title}</h2>"];
        foreach ($items as $item) {
            $q = $this->escape($item['question'] ?? $item['title'] ?? '');
            $a = $this->sanitizeHtml($item['answer'] ?? $item['content'] ?? '');
            $rows[] = $style === 'list'
                ? "<div class=\"tb-faq-item tb-faq-item-list\">
                    <h3 class=\"tb-faq-question\">{$q}</h3>
                    <div class=\"tb-faq-answer\">{$a}</div>
                </div>"
                : "<details class=\"tb-faq-item\">
                    <summary class=\"tb-faq-question\">{$q}</summary>
                    <div class=\"tb-faq-answer\">{$a}</div>
                </details>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-faq tb-faq-{$style}\">" . implode('', $rows) . "</div>";
    }

    // ── Support ───────────────────────────────────────────────────────────────

    protected function renderModuleSupportButton(array $props, string $id): string
    {
        $label    = $this->escape($props['label'] ?? 'پشتیبانی آنلاین');
        $style    = $props['style'] ?? 'floating';
        $position = $props['position'] ?? 'bottom-right';
        $bgColor  = $this->escapeAttr($props['bg_color'] ?? '#4f46e5');
        $posStyle = $position === 'bottom-left' ? 'left:1.5rem;right:auto;' : 'right:1.5rem;left:auto;';

        if ($style === 'floating') {
            return "<div id=\"tb-{$id}\">
                <a href=\"" . e(route('account.tickets.create')) . "\"
                   style=\"position:fixed;bottom:1.5rem;{$posStyle}background:{$bgColor};color:#fff;padding:0.75rem 1.25rem;border-radius:999px;text-decoration:none;font-weight:700;box-shadow:0 4px 16px rgba(0,0,0,0.2);z-index:9999;display:flex;align-items:center;gap:0.5rem;\"
                   aria-label=\"{$label}\">💬 {$label}</a>
            </div>";
        }

        return "<div id=\"tb-{$id}\" style=\"text-align:center;margin:1rem 0;\">
            <a href=\"" . e(route('account.tickets.create')) . "\" class=\"tb-btn\" style=\"background:{$bgColor};color:#fff;\">💬 {$label}</a>
        </div>";
    }

    // ── Social / Notifications ────────────────────────────────────────────────

    protected function renderModuleSocialLinksBar(array $props, string $id): string
    {
        $links = [];

        if (class_exists(\App\Models\SocialLink::class)) {
            $links = \App\Models\SocialLink::orderBy('order')->get()
                ->map(fn ($l) => ['url' => $l->url, 'label' => $l->title ?? $l->platform ?? ''])->toArray();
        }

        if (empty($links)) {
            return '';
        }

        $requestedStyle = $props['style'] ?? 'horizontal';
        $style = in_array($requestedStyle, ['vertical', 'icons-only'], true) ? $requestedStyle : 'horizontal';
        $requestedColor = $props['color'] ?? 'brand';
        $color = in_array($requestedColor, ['white', 'dark'], true) ? $requestedColor : 'brand';
        $requestedSize = $props['size'] ?? 'md';
        $size  = in_array($requestedSize, ['sm', 'lg'], true) ? $requestedSize : 'md';

        $colorCss = ['brand' => 'var(--tb-primary,#4f46e5)', 'white' => '#ffffff', 'dark' => '#111827'][$color];
        $fontSize = ['sm' => '0.85rem', 'md' => '1rem', 'lg' => '1.25rem'][$size];
        $padding  = ['sm' => '0.35rem 0.6rem', 'md' => '0.5rem 0.85rem', 'lg' => '0.65rem 1.1rem'][$size];
        $wrapStyle = 'display:flex;gap:0.5rem;flex-wrap:wrap;' . ($style === 'vertical' ? 'flex-direction:column;align-items:flex-start;' : 'align-items:center;');

        $items = [];
        foreach ($links as $link) {
            $url   = $this->escapeAttr($link['url'] ?? '#');
            $label = $this->escape($link['label'] ?? '');
            $shown = $style === 'icons-only' ? mb_substr($label, 0, 1) : $label;
            $items[] = "<a href=\"{$url}\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"tb-social-link\" aria-label=\"{$label}\" style=\"color:{$colorCss};font-size:{$fontSize};padding:{$padding};border:1px solid currentColor;border-radius:0.5rem;text-decoration:none;\">{$shown}</a>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-social-icons tb-social-icons-{$style}\" style=\"{$wrapStyle}\">" . implode('', $items) . "</div>";
    }

    protected function renderModuleNotificationsBar(array $props, string $id): string
    {
        $style   = ($props['style'] ?? 'top-bar') === 'floating' ? 'floating' : 'top-bar';
        $dismiss = ($props['show_dismiss'] ?? true) ? "<button onclick=\"this.closest('.tb-notifications-bar').remove()\" style=\"background:transparent;border:none;cursor:pointer;font-size:1.1rem;color:inherit;padding:0 0.25rem;\" aria-label=\"بستن\">×</button>" : '';

        if (!class_exists(\App\Models\SiteNotification::class)) {
            return '';
        }

        $notification = \App\Models\SiteNotification::where('is_active', true)->latest()->first();
        if (!$notification) {
            return '';
        }

        $text = $this->escape($notification->message ?? $notification->body ?? '');
        $link = $notification->link ?? '';

        $inner = $link
            ? "<a href=\"" . $this->escapeAttr($link) . "\" style=\"color:inherit;text-decoration:underline;\">{$text}</a>"
            : $text;

        $positionStyle = $style === 'floating'
            ? 'position:fixed;bottom:1rem;left:50%;transform:translateX(-50%);width:min(92vw,32rem);border-radius:0.75rem;box-shadow:0 8px 24px rgba(0,0,0,0.18);z-index:9998;'
            : '';

        return "<div id=\"tb-{$id}\" class=\"tb-notifications-bar tb-notifications-bar-{$style}\" style=\"background:#4f46e5;color:#fff;padding:0.6rem 1rem;text-align:center;display:flex;justify-content:center;align-items:center;gap:0.75rem;{$positionStyle}\">
            <span>{$inner}</span>{$dismiss}
        </div>";
    }

    // ── Education articles ────────────────────────────────────────────────────

    protected function renderModuleEduArticleGrid(array $props, string $id): string
    {
        $title  = $this->escape($props['title'] ?? 'مقالات آموزشی');
        $limit  = max(1, (int) ($props['limit'] ?? 6));
        $layout = $props['layout'] ?? 'grid';

        $colsClass = $layout === 'list' ? 'tb-grid-cols-1' : 'tb-grid-cols-3';

        // Use Post as fallback for educational articles if education model not available
        if (!class_exists(\App\Models\EduArticle::class)) {
            // Render as blog posts filtered by category if available
            return $this->renderModuleBlogPosts(array_merge($props, ['title' => $title]), $id);
        }

        return "<div id=\"tb-{$id}\" class=\"tb-module-edu\">
            <h2 class=\"tb-heading tb-section-title\">{$title}</h2>
            <p class=\"tb-text\">مقالات آموزشی از پنل مدیریت قابل مدیریت است.</p>
        </div>";
    }

    // ── Referral ─────────────────────────────────────────────────────────────

    protected function renderModuleReferralWidget(array $props, string $id): string
    {
        if (!SiteHelper::moduleEnabled('referral') || !auth()->check()) {
            return '';
        }

        $title    = $this->escape($props['title'] ?? 'دوستانت را دعوت کن');
        $subtitle = $this->escape($props['subtitle'] ?? 'با معرفی دوستان جایزه بگیر');
        $requestedStyle = $props['style'] ?? 'banner';
        $style    = in_array($requestedStyle, ['card', 'minimal'], true) ? $requestedStyle : 'banner';
        $showLink = $props['show_link'] ?? true;
        $link     = $showLink
            ? "<a href=\"" . e(route('account.referrals.index')) . "\" class=\"tb-btn tb-btn-primary\">مشاهده لینک معرفی</a>"
            : '';

        if ($style === 'minimal') {
            return "<div id=\"tb-{$id}\" class=\"tb-module-referral tb-module-referral-minimal\" style=\"display:flex;align-items:center;gap:0.75rem;padding:0.75rem 0;\">
                <span>🎁 <strong>{$title}</strong> — {$subtitle}</span>{$link}
            </div>";
        }
        if ($style === 'card') {
            return "<div id=\"tb-{$id}\" class=\"tb-module-referral tb-module-referral-card\" style=\"background:#fff;border:1px solid #e5e7eb;border-radius:0.75rem;padding:1.5rem;box-shadow:0 2px 8px rgba(0,0,0,0.06);text-align:center;\">
                <h3 style=\"font-weight:700;margin:0 0 0.5rem;\">🎁 {$title}</h3>
                <p style=\"color:#6b7280;margin:0 0 1rem;\">{$subtitle}</p>
                {$link}
            </div>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-module-referral tb-module-referral-banner\" style=\"background:#fef9c3;border:1px solid #eab308;border-radius:0.75rem;padding:1.5rem;text-align:center;\">
            <h3 style=\"font-weight:700;margin:0 0 0.5rem;\">🎁 {$title}</h3>
            <p style=\"color:#713f12;margin:0 0 1rem;\">{$subtitle}</p>
            {$link}
        </div>";
    }

    // ── WhatsApp ─────────────────────────────────────────────────────────────

    protected function renderModuleWhatsappWidget(array $props, string $id): string
    {
        if (!SiteHelper::moduleEnabled('whatsapp')) {
            return '';
        }

        $phone = trim((string) ($props['phone'] ?? '')) ?: \App\Models\Setting::get('whatsapp_phone', '');
        if (!$phone) {
            return '';
        }

        $label    = $this->escape($props['label'] ?? 'پشتیبانی واتساپ');
        $message  = \App\Models\Setting::get('whatsapp_message', '');
        $position = $props['position'] ?? 'bottom-right';
        $posStyle = $position === 'bottom-left' ? 'left:1.5rem;right:auto;' : 'right:1.5rem;left:auto;';

        $waNumber = preg_replace('/[^0-9]/', '', $phone);
        $waUrl    = 'https://wa.me/' . $waNumber . ($message ? '?text=' . urlencode($message) : '');

        return "<div id=\"tb-{$id}\">
            <a href=\"" . $this->escapeAttr($waUrl) . "\" target=\"_blank\" rel=\"noopener noreferrer\"
               style=\"position:fixed;bottom:1.5rem;{$posStyle}background:#25D366;color:#fff;padding:0.75rem 1.25rem;border-radius:999px;text-decoration:none;font-weight:700;box-shadow:0 4px 16px rgba(0,0,0,0.2);z-index:9999;display:flex;align-items:center;gap:0.5rem;\"
               aria-label=\"{$label}\">💬 {$label}</a>
        </div>";
    }

    // ── Form embed ───────────────────────────────────────────────────────────

    protected function renderModuleFormEmbed(array $props, string $id): string
    {
        if (!SiteHelper::moduleEnabled('forms')) {
            return '';
        }

        $formId = trim((string) ($props['form_id'] ?? ''));
        if ($formId === '' || !class_exists(\SaaSPlatform\Modules\Forms\Models\Form::class)) {
            return "<div id=\"tb-{$id}\" class=\"tb-module-form-embed\"><p class=\"tb-text\">فرم انتخاب نشده است.</p></div>";
        }

        $form = \SaaSPlatform\Modules\Forms\Models\Form::where('is_active', true)
            ->where(function ($q) use ($formId) {
                $q->where('id', $formId)->orWhere('slug', $formId);
            })
            ->first();

        if (!$form) {
            return "<div id=\"tb-{$id}\" class=\"tb-module-form-embed\"><p class=\"tb-text\">فرم یافت نشد.</p></div>";
        }

        $title     = $this->escape($props['title'] ?? '');
        $showTitle = $props['show_title'] ?? true;
        $titleHtml = ($showTitle && $title) ? "<h2 class=\"tb-heading tb-section-title\">{$title}</h2>" : '';
        $src       = $this->escapeAttr(route('forms.show', $form->slug));
        $frameTitle = $this->escapeAttr($form->title ?? 'فرم');
        $requestedStyle = $props['style'] ?? 'card';
        $style     = in_array($requestedStyle, ['minimal', 'full'], true) ? $requestedStyle : 'card';

        $wrapStyle = match ($style) {
            'minimal' => '',
            'full'    => 'width:100%;',
            default   => 'max-width:40rem;margin:0 auto;padding:1.5rem;border:1px solid #e5e7eb;border-radius:0.75rem;box-shadow:0 2px 8px rgba(0,0,0,0.05);',
        };

        return "<div id=\"tb-{$id}\" class=\"tb-module-form-embed tb-module-form-embed-{$style}\" style=\"{$wrapStyle}\">
            {$titleHtml}
            <iframe src=\"{$src}\" loading=\"lazy\" style=\"width:100%;min-height:520px;border:0;\" title=\"{$frameTitle}\"></iframe>
        </div>";
    }

    // ── Reviews (cross-product, distinct from renderTestimonials) ──────────────

    protected function renderModuleReviewsGrid(array $props, string $id): string
    {
        if (!SiteHelper::moduleEnabled('reviews')) {
            return '';
        }

        $title      = $this->escape($props['title'] ?? 'نظرات مشتریان');
        $limit      = max(1, (int) ($props['limit'] ?? 6));
        $showAvatar = $props['show_avatar'] ?? true;
        $style      = ($props['style'] ?? 'cards') === 'minimal' ? 'minimal' : 'cards';

        if (!class_exists(\App\Models\ProductReview::class)) {
            return "<div id=\"tb-{$id}\" class=\"tb-module-reviews\"><h2 class=\"tb-heading tb-section-title\">{$title}</h2></div>";
        }

        $reviews = \App\Models\ProductReview::with('product:id,title,slug')
            ->approved()
            ->latest()
            ->take($limit)
            ->get();

        if ($reviews->isEmpty()) {
            return "<div id=\"tb-{$id}\" class=\"tb-module-reviews\">
                <h2 class=\"tb-heading tb-section-title\">{$title}</h2>
                <p class=\"tb-text\">هنوز نظری ثبت نشده است.</p>
            </div>";
        }

        $cards = [];
        foreach ($reviews as $review) {
            $name        = $this->escape($review->guest_name ?: 'کاربر');
            $body        = $this->escape(Str::limit($review->body ?? '', 180));
            $rating      = (int) ($review->rating ?? 5);
            $stars       = str_repeat('★', min(5, max(0, $rating))) . str_repeat('☆', 5 - min(5, max(0, $rating)));
            $avatar      = $showAvatar ? "<div class=\"tb-review-avatar\">" . mb_substr($name, 0, 1) . "</div>" : '';
            $productName = $review->product->title ?? '';
            $productLine = $productName ? "<p class=\"tb-review-product\">" . $this->escape($productName) . "</p>" : '';
            $cards[] = $style === 'minimal'
                ? "<div class=\"tb-review-minimal\" style=\"padding:0.75rem 0;border-bottom:1px solid #e5e7eb;\">{$avatar}<span class=\"tb-testimonial-rating\">{$stars}</span> <strong>{$name}</strong>: <span class=\"tb-testimonial-text\">{$body}</span>{$productLine}</div>"
                : "<div class=\"tb-testimonial-card\">{$avatar}<div class=\"tb-testimonial-rating\">{$stars}</div><p class=\"tb-testimonial-text\">{$body}</p><p class=\"tb-testimonial-author\">— {$name}</p>{$productLine}</div>";
        }

        $wrapClass = $style === 'minimal' ? 'tb-reviews-minimal-list' : 'tb-testimonials-grid';
        return "<div id=\"tb-{$id}\" class=\"tb-module-reviews tb-module-reviews-{$style}\">
            <h2 class=\"tb-heading tb-section-title\">{$title}</h2>
            <div class=\"{$wrapClass}\">" . implode('', $cards) . "</div>
        </div>";
    }

    // ── Motion Graphics bridge ──────────────────────────────────────────────
    //
    // Reuses the existing <x-motion:graphics name="..."> runtime component
    // verbatim (resources/views/components/motion/graphics.blade.php) — this
    // method contains zero Motion resolution/rendering/entitlement logic of
    // its own. That component already fails closed on every unsafe case
    // (missing manifest, unpublished asset, unauthorized site, malformed
    // reference), so no duplicate license/ownership check belongs here.

    protected function renderModuleMotion(array $props, string $id): string
    {
        if (! ($props['enabled'] ?? true)) {
            return '';
        }

        $assetId = trim((string) ($props['motion_asset_id'] ?? ''));
        if ($assetId === '') {
            return '';
        }

        $html = '';
        try {
            $html = trim(\Illuminate\Support\Facades\Blade::render(
                '<x-motion.graphics :name="$assetId" class="tb-module-motion" />',
                ['assetId' => $assetId]
            ));
        } catch (\Throwable $e) {
            $html = '';
        }

        if ($html === '') {
            $fallbackImage = trim((string) ($props['fallback_image'] ?? ''));
            if ($fallbackImage === '') {
                // No published/assigned/authorized asset and no fallback
                // image configured — render nothing, page stays intact.
                return '';
            }

            return "<div id=\"tb-{$id}\" class=\"tb-module-motion-fallback\"><img src=\"" . $this->escapeAttr($fallbackImage) . "\" alt=\"\" loading=\"lazy\"></div>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-module-motion\" data-placement=\"" . $this->escapeAttr($props['placement'] ?? 'section') . "\">{$html}</div>";
    }

    // ── Generic placeholder ───────────────────────────────────────────────────

    // ── Account / Advertisement / Business card / Content / Invoice / Search ───
    // Builder integrity audit (2026-09-11): 8 real implementations for
    // canonical types that previously had no dispatch branch (§ above).

    protected function renderModuleAccountQuickLinks(array $props, string $id): string
    {
        if (!auth()->check()) {
            return '';
        }
        $requestedStyle = $props['style'] ?? 'buttons';
        $style      = in_array($requestedStyle, ['list', 'dropdown'], true) ? $requestedStyle : 'buttons';
        $showNotif  = $props['show_notifications'] ?? true;
        $showAvatar = $props['show_avatar'] ?? true;

        $links = [
            ['url' => route('account.dashboard'), 'label' => 'داشبورد'],
            ['url' => route('account.orders.index'), 'label' => 'سفارش‌ها'],
            ['url' => route('account.profile.index'), 'label' => 'پروفایل'],
        ];
        if ($showNotif) {
            $links[] = ['url' => route('account.notifications.index'), 'label' => 'اعلان‌ها'];
        }

        $avatarHtml = '';
        if ($showAvatar) {
            $user = auth()->user();
            $name = $this->escape($user?->name ?: 'کاربر');
            $avatarUrl = $user?->avatar ? ImageHelper::url($user->avatar) : null;
            $avatarHtml = $avatarUrl
                ? "<img src=\"" . $this->escapeAttr($avatarUrl) . "\" alt=\"{$name}\" style=\"width:2rem;height:2rem;border-radius:999px;object-fit:cover;\">"
                : "<span style=\"display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;border-radius:999px;background:#4f46e5;color:#fff;font-size:0.85rem;\">" . mb_substr($name, 0, 1) . "</span>";
        }

        $items = array_map(
            fn ($l) => "<a href=\"" . $this->escapeAttr($l['url']) . "\" class=\"tb-btn tb-btn-secondary\">" . $this->escape($l['label']) . "</a>",
            $links
        );

        $wrapStyle = $style === 'list'
            ? 'display:flex;flex-direction:column;gap:0.5rem;align-items:flex-start;'
            : 'display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;';

        return "<div id=\"tb-{$id}\" class=\"tb-module-account-links tb-module-account-links-{$style}\" style=\"{$wrapStyle}\">{$avatarHtml}" . implode('', $items) . "</div>";
    }

    protected function renderModuleAdvertisementZone(array $props, string $id): string
    {
        if (!SiteHelper::moduleEnabled('advertisements') || !class_exists(\App\Models\Advertisement::class)) {
            return '';
        }
        $zone = trim((string) ($props['zone_name'] ?? 'banner-main'));
        $position = $props['position'] ?? 'banner';

        $ad = \App\Models\Advertisement::active()->position($zone)->orderBy('order')->first();
        if (!$ad) {
            return '';
        }

        $title = $this->escape($ad->title ?? '');
        $link  = $this->escapeAttr($ad->link ?? '#');
        $img   = !empty($ad->image_path)
            ? "<img src=\"" . e(ImageHelper::url($ad->image_path)) . "\" alt=\"{$title}\" loading=\"lazy\" style=\"max-width:100%;display:block;\">"
            : "<p class=\"tb-text\">" . $this->escape($ad->content ?? '') . "</p>";

        return "<div id=\"tb-{$id}\" class=\"tb-module-advertisement tb-module-advertisement-{$position}\">
            <a href=\"{$link}\" target=\"_blank\" rel=\"noopener noreferrer\">{$img}</a>
        </div>";
    }

    protected function renderModuleBusinessCardEmbed(array $props, string $id): string
    {
        if (!SiteHelper::moduleEnabled('business_card') || !class_exists(\App\Models\BusinessCard::class)) {
            return '';
        }
        $slug = trim((string) ($props['card_slug'] ?? ''));
        if ($slug === '') {
            return "<div id=\"tb-{$id}\" class=\"tb-module-business-card\"><p class=\"tb-text\">کارت ویزیتی انتخاب نشده است.</p></div>";
        }

        $card = \App\Models\BusinessCard::where('slug', $slug)->where('is_active', true)->first();
        if (!$card) {
            return "<div id=\"tb-{$id}\" class=\"tb-module-business-card\"><p class=\"tb-text\">کارت ویزیت یافت نشد.</p></div>";
        }

        $style   = ($props['style'] ?? 'full') === 'compact' ? 'compact' : 'full';
        $url     = route('business-card.show', $card->slug);
        $name    = $this->escape($card->name ?? '');
        $title   = $this->escape($card->title ?? '');
        $company = $this->escape($card->company ?? '');
        $avatar  = !empty($card->avatar)
            ? "<img src=\"" . e(ImageHelper::url($card->avatar)) . "\" alt=\"{$name}\" style=\"width:3.5rem;height:3.5rem;border-radius:999px;object-fit:cover;\">"
            : '';

        $subtitle = $style === 'compact' ? '' : "<p class=\"tb-text\" style=\"margin:0.15rem 0 0;color:#6b7280;\">{$title} " . ($company ? "— {$company}" : '') . "</p>";

        return "<a id=\"tb-{$id}\" href=\"" . $this->escapeAttr($url) . "\" class=\"tb-module-business-card\" style=\"display:flex;align-items:center;gap:0.85rem;padding:1rem;border:1px solid #e5e7eb;border-radius:0.75rem;text-decoration:none;color:inherit;max-width:24rem;\">
            {$avatar}
            <span><strong>{$name}</strong>{$subtitle}</span>
        </a>";
    }

    protected function renderModuleContentCards(array $props, string $id): string
    {
        if (!class_exists(\App\Models\SiteContent::class)) {
            return '';
        }
        $title       = $this->escape($props['title'] ?? 'مطالب');
        $limit       = max(1, (int) ($props['limit'] ?? 3));
        $requestedStyle = $props['style'] ?? 'cards';
        $style       = in_array($requestedStyle, ['grid', 'list'], true) ? $requestedStyle : 'cards';
        $showExcerpt = $props['show_excerpt'] ?? true;

        $contents = \App\Models\SiteContent::query()
            ->where('status', \App\Models\SiteContent::STATUS_PUBLISHED)
            ->where('type', '!=', \App\Models\SiteContent::TYPE_HELP)
            ->latest('published_at')
            ->take($limit)
            ->get();

        $titleHtml = $title ? "<h2 class=\"tb-heading tb-section-title\">{$title}</h2>" : '';
        if ($contents->isEmpty()) {
            return "<div id=\"tb-{$id}\" class=\"tb-module-content-cards\">{$titleHtml}</div>";
        }

        $colsClass = $style === 'list' ? 'tb-grid-cols-1' : 'tb-grid-cols-3';
        $cards = [];
        foreach ($contents as $content) {
            $url     = route('content.show', $content->slug);
            $t       = $this->escape($content->title);
            $excerpt = $showExcerpt ? "<p class=\"tb-blog-card-excerpt\">" . $this->escape(Str::limit(strip_tags($content->excerpt ?? $content->body ?? ''), 110)) . "</p>" : '';
            $cards[] = "<a href=\"" . $this->escapeAttr($url) . "\" class=\"tb-blog-card\"><div class=\"tb-blog-card-body\"><h3 class=\"tb-blog-card-title\">{$t}</h3>{$excerpt}</div></a>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-module-content-cards\">
            {$titleHtml}
            <div class=\"tb-grid {$colsClass} tb-gap-lg\">" . implode('', $cards) . "</div>
        </div>";
    }

    protected function renderModuleInvoiceQuickPay(array $props, string $id): string
    {
        if (!SiteHelper::moduleEnabled('invoice')) {
            return '';
        }
        $title       = $this->escape($props['title'] ?? 'پرداخت فاکتور');
        $placeholder = $this->escapeAttr($props['placeholder'] ?? 'شماره فاکتور را وارد کنید');
        $action      = route('invoice.show', ['invoiceNumber' => '__NUMBER__']);
        $actionBase  = $this->escapeAttr(str_replace('__NUMBER__', '', $action));

        return "<div id=\"tb-{$id}\" class=\"tb-module-invoice-pay\">
            <h2 class=\"tb-heading tb-section-title\">{$title}</h2>
            <form method=\"get\" action=\"#\" onsubmit=\"window.location.href='{$actionBase}'+encodeURIComponent(this.elements['invoice_number'].value);return false;\" style=\"display:flex;gap:0.5rem;max-width:24rem;\">
                <input type=\"text\" name=\"invoice_number\" placeholder=\"{$placeholder}\" required style=\"flex:1;padding:0.6rem 0.85rem;border:1px solid #d1d5db;border-radius:0.5rem;\">
                <button type=\"submit\" class=\"tb-btn tb-btn-primary\">مشاهده و پرداخت</button>
            </form>
        </div>";
    }

    protected function renderModuleShopCountdown(array $props, string $id): string
    {
        if (!SiteHelper::shopEnabled()) {
            return '';
        }
        $endDate = trim((string) ($props['end_date'] ?? ''));
        if ($endDate === '' || !strtotime($endDate) || strtotime($endDate) <= time()) {
            return '';
        }
        $promoText = $this->escape($props['promo_text'] ?? 'پیشنهاد ویژه — فرصت محدود!');
        $bgColor   = $this->escapeAttr($props['bg_color'] ?? '#ef4444');
        $endIso    = $this->escapeAttr(date('c', strtotime($endDate)));

        return "<div id=\"tb-{$id}\" class=\"tb-module-shop-countdown\" style=\"background:{$bgColor};color:#fff;padding:1.25rem;border-radius:0.75rem;text-align:center;\">
            <p style=\"margin:0 0 0.5rem;font-weight:700;\">{$promoText}</p>
            <div class=\"tb-countdown-timer\" data-end=\"{$endIso}\" style=\"font-size:1.5rem;font-weight:800;font-variant-numeric:tabular-nums;\">--:--:--:--</div>
            <script>(function(){var el=document.currentScript.previousElementSibling;var end=new Date(el.dataset.end).getTime();function tick(){var d=end-Date.now();if(d<=0){el.textContent='به پایان رسید';return;}var days=Math.floor(d/86400000),h=Math.floor(d/3600000%24),m=Math.floor(d/60000%60),s=Math.floor(d/1000%60);el.textContent=days+'روز '+String(h).padStart(2,'0')+':'+String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');setTimeout(tick,1000);}tick();})();</script>
        </div>";
    }

    protected function renderModuleShopSearchBox(array $props, string $id): string
    {
        if (!SiteHelper::shopEnabled()) {
            return '';
        }
        $placeholder = $this->escapeAttr($props['placeholder'] ?? 'جستجو در محصولات...');
        $style       = ($props['style'] ?? 'bar') === 'box' ? 'box' : 'bar';
        $action      = $this->escapeAttr(route('shop.index'));
        $boxStyle    = $style === 'box' ? 'padding:1.25rem;border:1px solid #e5e7eb;border-radius:0.75rem;' : '';

        return "<form id=\"tb-{$id}\" method=\"get\" action=\"{$action}\" class=\"tb-module-shop-search tb-module-shop-search-{$style}\" style=\"display:flex;gap:0.5rem;max-width:28rem;{$boxStyle}\">
            <input type=\"text\" name=\"q\" placeholder=\"{$placeholder}\" style=\"flex:1;padding:0.6rem 0.85rem;border:1px solid #d1d5db;border-radius:0.5rem;\">
            <button type=\"submit\" class=\"tb-btn tb-btn-secondary\">🔍</button>
        </form>";
    }

    protected function renderModuleSiteSearch(array $props, string $id): string
    {
        if (!SiteHelper::moduleEnabled('advanced_search')) {
            return '';
        }
        $placeholder = $this->escapeAttr($props['placeholder'] ?? 'جستجو در سایت...');
        $requestedStyle = $props['style'] ?? 'bar';
        $style       = in_array($requestedStyle, ['card', 'hero'], true) ? $requestedStyle : 'bar';
        $showSuggest = $props['show_suggestions'] ?? true;
        $action      = $this->escapeAttr(route('search.index'));
        $wrapStyle   = $style === 'card'
            ? 'padding:1.5rem;border:1px solid #e5e7eb;border-radius:0.75rem;'
            : ($style === 'hero' ? 'padding:2.5rem 1rem;text-align:center;' : '');

        if (!$showSuggest) {
            return "<form id=\"tb-{$id}\" method=\"get\" action=\"{$action}\" class=\"tb-module-site-search tb-module-site-search-{$style}\" style=\"{$wrapStyle}\">
                <div style=\"display:flex;gap:0.5rem;max-width:32rem;margin:0 auto;\">
                    <input type=\"text\" name=\"q\" placeholder=\"{$placeholder}\" style=\"flex:1;padding:0.7rem 1rem;border:1px solid #d1d5db;border-radius:0.5rem;\">
                    <button type=\"submit\" class=\"tb-btn tb-btn-primary\">جستجو</button>
                </div>
            </form>";
        }

        $suggestUrl = $this->escapeAttr(route('search.suggest'));
        return "<form id=\"tb-{$id}\" method=\"get\" action=\"{$action}\" class=\"tb-module-site-search tb-module-site-search-{$style}\" style=\"{$wrapStyle}\">
            <div style=\"position:relative;display:flex;gap:0.5rem;max-width:32rem;margin:0 auto;\">
                <input type=\"text\" name=\"q\" placeholder=\"{$placeholder}\" autocomplete=\"off\" class=\"tb-site-search-input\" style=\"flex:1;padding:0.7rem 1rem;border:1px solid #d1d5db;border-radius:0.5rem;\">
                <button type=\"submit\" class=\"tb-btn tb-btn-primary\">جستجو</button>
                <div class=\"tb-site-search-suggestions\" style=\"display:none;position:absolute;top:100%;right:0;left:0;background:#fff;border:1px solid #e5e7eb;border-radius:0.5rem;margin-top:0.25rem;box-shadow:0 4px 12px rgba(0,0,0,0.08);z-index:20;max-height:16rem;overflow-y:auto;\"></div>
            </div>
            <script>(function(){
                var form = document.getElementById('tb-{$id}');
                var input = form.querySelector('.tb-site-search-input');
                var box = form.querySelector('.tb-site-search-suggestions');
                var timer = null;
                input.addEventListener('input', function(){
                    clearTimeout(timer);
                    var q = input.value.trim();
                    if (q.length < 2) { box.style.display = 'none'; box.innerHTML = ''; return; }
                    timer = setTimeout(function(){
                        fetch('{$suggestUrl}?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                            .then(function(r){ return r.ok ? r.json() : []; })
                            .then(function(items){
                                items = Array.isArray(items) ? items : (items.results || items.data || []);
                                if (!items.length) { box.style.display = 'none'; box.innerHTML = ''; return; }
                                box.innerHTML = items.slice(0, 8).map(function(it){
                                    var label = (typeof it === 'string') ? it : (it.title || it.label || it.name || '');
                                    var url = (typeof it === 'object' && it.url) ? it.url : null;
                                    var esc = String(label).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                                    return url
                                        ? '<a href=\"' + url + '\" style=\"display:block;padding:0.5rem 0.85rem;color:inherit;text-decoration:none;border-bottom:1px solid #f3f4f6;\">' + esc + '</a>'
                                        : '<div style=\"padding:0.5rem 0.85rem;border-bottom:1px solid #f3f4f6;\">' + esc + '</div>';
                                }).join('');
                                box.style.display = 'block';
                            })
                            .catch(function(){ box.style.display = 'none'; });
                    }, 250);
                });
                document.addEventListener('click', function(e){ if (!form.contains(e.target)) { box.style.display = 'none'; } });
            })();</script>
        </form>";
    }

    protected function renderModuleGenericPlaceholder(string $type, array $props, string $id): string
    {
        // Silent in production — module may not be enabled
        return '';
    }

    // ── Carousel runtime (real slider/carousel behavior) ────────────────────────
    //
    // Builder integrity audit (2026-09-11): the 'slider' bare type had an
    // autoplay/interval schema but zero renderer (fell to renderFallback(),
    // a static wrapper div — the props were dead). 'testimonials_slider'
    // had a real canonical renderer, but that renderer is a static grid
    // (`.tb-testimonials-grid`), not a slider, despite the name. No
    // slider/carousel JS or CSS existed anywhere in either repository.
    // This is the one canonical carousel runtime for every slider-shaped
    // Canvas type on this host — renderSlider(), renderTestimonialsSlider()
    // (an intentional host override of the canonical static-grid version,
    // matching this file's existing override pattern), and
    // renderProductSlider() all call it. No second carousel implementation
    // belongs anywhere else in this class.

    /**
     * @param string[] $slidesHtml Pre-rendered inner HTML for each slide.
     */
    private function renderCarousel(array $slidesHtml, string $id, bool $autoplay, int $interval, string $extraClass = ''): string
    {
        $slidesHtml = array_values(array_filter($slidesHtml, fn ($s) => trim((string) $s) !== ''));
        if (empty($slidesHtml)) {
            return '';
        }
        if (count($slidesHtml) === 1) {
            return "<div id=\"tb-{$id}\" class=\"tb-carousel-single {$extraClass}\">{$slidesHtml[0]}</div>";
        }

        $interval = max(1000, $interval);
        $slides = [];
        $dots = [];
        foreach ($slidesHtml as $i => $html) {
            $slides[] = "<div class=\"tb-carousel-slide\" style=\"flex:0 0 100%;min-width:0;\">{$html}</div>";
            $num = $i + 1;
            $dots[] = "<button type=\"button\" class=\"tb-carousel-dot\" aria-label=\"اسلاید {$num}\" style=\"width:0.55rem;height:0.55rem;padding:0;border-radius:999px;border:0;background:#d1d5db;cursor:pointer;\"></button>";
        }

        $autoplayAttr = $autoplay ? 'true' : 'false';

        return "<div id=\"tb-{$id}\" class=\"tb-carousel {$extraClass}\" data-autoplay=\"{$autoplayAttr}\" data-interval=\"{$interval}\" style=\"position:relative;\">
            <div class=\"tb-carousel-viewport\" style=\"overflow:hidden;\">
                <div class=\"tb-carousel-track\" style=\"display:flex;transition:transform .45s ease;\">" . implode('', $slides) . "</div>
            </div>
            <button type=\"button\" class=\"tb-carousel-prev\" aria-label=\"قبلی\" style=\"position:absolute;top:50%;right:0.25rem;transform:translateY(-50%);width:2.25rem;height:2.25rem;border-radius:999px;border:1px solid #e5e7eb;background:#fff;cursor:pointer;font-size:1.1rem;line-height:1;z-index:2;\">‹</button>
            <button type=\"button\" class=\"tb-carousel-next\" aria-label=\"بعدی\" style=\"position:absolute;top:50%;left:0.25rem;transform:translateY(-50%);width:2.25rem;height:2.25rem;border-radius:999px;border:1px solid #e5e7eb;background:#fff;cursor:pointer;font-size:1.1rem;line-height:1;z-index:2;\">›</button>
            <div class=\"tb-carousel-dots\" style=\"display:flex;justify-content:center;gap:0.4rem;margin-top:0.85rem;\">" . implode('', $dots) . "</div>
            <script>(function(){
                function init(root){
                    if (!root || root.dataset.tbCarouselInit) return;
                    root.dataset.tbCarouselInit = '1';
                    var track = root.querySelector('.tb-carousel-track');
                    var slides = Array.prototype.slice.call(root.querySelectorAll('.tb-carousel-slide'));
                    var dots = Array.prototype.slice.call(root.querySelectorAll('.tb-carousel-dot'));
                    var prev = root.querySelector('.tb-carousel-prev');
                    var next = root.querySelector('.tb-carousel-next');
                    var idx = 0, timer = null;
                    function paint(){
                        track.style.transform = 'translateX(' + (idx * 100) + '%)';
                        dots.forEach(function(d, j){ d.style.background = (j === idx) ? 'var(--tb-primary,#4f46e5)' : '#d1d5db'; d.setAttribute('aria-current', j === idx ? 'true' : 'false'); });
                    }
                    function go(i){ idx = ((i % slides.length) + slides.length) % slides.length; paint(); }
                    function stop(){ if (timer) { clearInterval(timer); timer = null; } }
                    function start(){ stop(); if (root.dataset.autoplay === 'true' && slides.length > 1) { timer = setInterval(function(){ go(idx + 1); }, parseInt(root.dataset.interval, 10) || 5000); } }
                    if (prev) prev.addEventListener('click', function(){ go(idx - 1); start(); });
                    if (next) next.addEventListener('click', function(){ go(idx + 1); start(); });
                    dots.forEach(function(d, j){ d.addEventListener('click', function(){ go(j); start(); }); });
                    var touchX = null;
                    root.addEventListener('touchstart', function(e){ touchX = e.touches[0].clientX; }, {passive:true});
                    root.addEventListener('touchend', function(e){
                        if (touchX === null) return;
                        var dx = e.changedTouches[0].clientX - touchX;
                        if (Math.abs(dx) > 40) { go(dx > 0 ? idx - 1 : idx + 1); start(); }
                        touchX = null;
                    }, {passive:true});
                    paint();
                    start();
                }
                init(document.getElementById('tb-{$id}'));
            })();</script>
        </div>";
    }

    /**
     * Builder integrity fix (2026-09-12): a `slider` slide is a plain data
     * record ({image, title, subtitle, link} — config/builder-elements.php
     * 'slider'.schema.slides.item_schema), never a self-describing Builder
     * block. The previous implementation fed each slide into
     * `renderBlock()`/`ElementSchemaRegistry::validate()` as if it were one
     * — validation requires a `type` key slides don't have, so every slide
     * silently rendered as an `[invalid] text` placeholder instead of its
     * actual content. Slides are built directly from their own fields here,
     * matching the pattern `renderTestimonialsSlider()`/`renderProductSlider()`
     * already use correctly for their own repeater/query data.
     */
    protected function renderSlider(array $props, string $inner, string $id): string
    {
        $slides = $props['slides'] ?? [];
        if (!is_array($slides)) {
            $slides = [];
        }

        $slidesHtml = [];
        foreach ($slides as $slide) {
            if (!is_array($slide)) {
                continue;
            }

            // resolveMediaProps() already resolved a canonical {media_id}
            // shape (via CustomerMediaResolver::MEDIA_REPEATERS['slider'])
            // to a plain URL string before renderBlock() reached this
            // method — this method only ever sees a renderable string.
            $image    = trim((string) ($slide['image'] ?? ''));
            $title    = $this->escape($slide['title'] ?? '');
            $subtitle = $this->escape($slide['subtitle'] ?? '');
            $link     = trim((string) ($slide['link'] ?? ''));

            $imgHtml = $image !== ''
                ? '<img src="' . $this->escapeAttr($image) . '" alt="' . $this->escapeAttr($slide['title'] ?? '') . '" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;" loading="lazy">'
                : '';

            $captionHtml = ($title !== '' || $subtitle !== '')
                ? '<div class="tb-slide-caption" style="position:relative;z-index:1;padding:2.5rem 1.5rem;color:#fff;text-align:center;text-shadow:0 1px 4px rgba(0,0,0,.55);">'
                    . ($title !== '' ? "<h2 class=\"tb-slide-title\" style=\"margin:0 0 .5rem;font-size:1.75rem;font-weight:700;\">{$title}</h2>" : '')
                    . ($subtitle !== '' ? "<p class=\"tb-slide-subtitle\" style=\"margin:0;font-size:1.05rem;opacity:.95;\">{$subtitle}</p>" : '')
                    . '</div>'
                : '';

            $slideInner = '<div class="tb-slide" style="position:relative;min-height:22rem;display:flex;align-items:center;justify-content:center;background:#111827;overflow:hidden;">'
                . $imgHtml . $captionHtml . '</div>';

            $slidesHtml[] = $link !== ''
                ? '<a href="' . $this->escapeUrl($link) . '" class="tb-slide-link" style="display:block;color:inherit;text-decoration:none;">' . $slideInner . '</a>'
                : $slideInner;
        }

        if (empty($slidesHtml)) {
            return '';
        }

        return $this->renderCarousel(
            $slidesHtml,
            $id,
            (bool) ($props['autoplay'] ?? true),
            (int) ($props['interval'] ?? 5000),
            'tb-slider'
        );
    }

    /**
     * Host override of the canonical (static-grid) renderTestimonialsSlider
     * — see the "Carousel runtime" doc comment above for why this
     * particular type is overridden here rather than fixed in the shared
     * package.
     */
    protected function renderTestimonialsSlider(array $props, string $inner, string $id): string
    {
        $title = $this->escape($props['title'] ?? 'نظرات مشتریان');

        $rawItems = $props['items'] ?? '[]';
        $items = is_array($rawItems) ? $rawItems : (json_decode((string) $rawItems, true) ?? []);

        $slidesHtml = [];
        foreach ($items as $t) {
            $name   = $this->escape($t['name'] ?? '');
            $role   = $this->escape($t['role'] ?? '');
            $text   = $this->escape($t['text'] ?? '');
            $rating = max(1, min(5, (int) ($t['rating'] ?? 5)));
            $stars  = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
            $initials = mb_substr($name, 0, 1, 'UTF-8');
            $slidesHtml[] = "<div class=\"tb-testimonial-card\" style=\"max-width:36rem;margin:0 auto;text-align:center;\">
                <div class=\"tb-testimonial-stars\">{$stars}</div>
                <p class=\"tb-testimonial-text\">«{$text}»</p>
                <div class=\"tb-testimonial-author\" style=\"display:flex;justify-content:center;align-items:center;gap:0.5rem;\">
                    <div class=\"tb-testimonial-avatar\">{$initials}</div>
                    <div><div class=\"tb-testimonial-name\">{$name}</div><div class=\"tb-testimonial-role\">{$role}</div></div>
                </div>
            </div>";
        }

        if (empty($slidesHtml)) {
            return "<div id=\"tb-{$id}\" class=\"tb-testimonials tb-section\"><h2 class=\"tb-heading tb-section-title\">{$title}</h2></div>";
        }

        $carousel = $this->renderCarousel(
            $slidesHtml,
            $id . '-track',
            (bool) ($props['autoplay'] ?? true),
            (int) ($props['interval'] ?? 5000),
            'tb-testimonials-slider'
        );

        return "<div id=\"tb-{$id}\" class=\"tb-testimonials tb-section\">
            <div class=\"tb-container\">
                <div class=\"tb-testimonials-header\"><h2 class=\"tb-features-section-title\">{$title}</h2></div>
                {$carousel}
            </div>
        </div>";
    }

    protected function renderProductSlider(array $props, string $inner, string $id): string
    {
        if (!SiteHelper::shopEnabled()) {
            return '';
        }
        $title = $this->escape($props['title'] ?? 'محصولات منتخب');
        $limit = max(1, (int) ($props['limit'] ?? 6));
        $products = Product::query()->active()->latest()->take($limit)->get();

        $titleHtml = $title ? "<h2 class=\"tb-heading tb-section-title\">{$title}</h2>" : '';
        if ($products->isEmpty()) {
            return "<div id=\"tb-{$id}\" class=\"tb-module-products\">{$titleHtml}</div>";
        }

        $slidesHtml = [];
        foreach ($products as $product) {
            $url   = route('shop.show', $product->slug);
            $t     = $this->escape($product->title);
            $price = CurrencyHelper::formatPrice($product->effective_price) . ' ' . CurrencyHelper::unitLabel();
            $imgHtml = $product->image
                ? "<div class=\"tb-product-card-img\"><img src=\"" . e(ImageHelper::url($product->image)) . "\" alt=\"{$t}\" loading=\"lazy\"></div>"
                : "<div class=\"tb-product-card-img tb-product-card-noimg\">📦</div>";
            $slidesHtml[] = "<a href=\"{$url}\" class=\"tb-product-card\" style=\"max-width:20rem;margin:0 auto;display:block;\">{$imgHtml}
                <div class=\"tb-product-card-inner\">
                    <h3 class=\"tb-product-title\">{$t}</h3>
                    <div class=\"tb-product-price\">{$price}</div>
                </div>
            </a>";
        }

        $carousel = $this->renderCarousel(
            $slidesHtml,
            $id . '-track',
            (bool) ($props['autoplay'] ?? true),
            (int) ($props['interval'] ?? 5000),
            'tb-product-slider'
        );

        return "<div id=\"tb-{$id}\" class=\"tb-module-products\">{$titleHtml}{$carousel}</div>";
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    // Phase 2j: escape()/escapeAttr() removed — byte-identical duplicates of
    // the inherited canonical implementation (via BlockRenderer), and their
    // narrower `string $s` signature became LSP-incompatible once canonical
    // widened to `mixed $s` (Phase 2j's malformed-_style-input safety fix).
    // Phase 3C: sanitizeHtml() removed for the same reason — this override
    // was a byte-identical (and, until Phase 3C fixed the canonical one,
    // equally vulnerable) duplicate of the inherited implementation. Do not
    // re-add it here; the canonical attribute-stripping fix must not be
    // silently shadowed by a stale local copy again.
}
