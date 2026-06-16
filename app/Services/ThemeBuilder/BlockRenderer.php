<?php

namespace App\Services\ThemeBuilder;

use App\Helpers\CurrencyHelper;
use App\Models\Post;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * رندرکنندهٔ بلوک‌های Theme Builder در سایت مشتری.
 * از payload ارسالی از saas-platform برای ساخت هدر/فوتر استفاده می‌کند.
 * قبل از رندر، بلوک با ElementSchemaRegistry اعتبارسنجی می‌شود؛ در صورت خطا یا exception
 * به‌جای خراب شدن صفحه، بلوک placeholder نمایش داده می‌شود.
 */
class BlockRenderer
{
    protected array $context = [];

    /** @var ElementSchemaRegistry|null */
    protected $schemaRegistry;

    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function setSchemaRegistry(?ElementSchemaRegistry $registry): self
    {
        $this->schemaRegistry = $registry;
        return $this;
    }

    /**
     * رندر آرایه‌ای از بلوک‌ها (مثلاً header_blocks یا footer_blocks).
     */
    public function renderBlocks(?array $blocks): string
    {
        if (empty($blocks) || !is_array($blocks)) {
            return '';
        }
        $html = [];
        foreach ($blocks as $block) {
            $html[] = $this->renderBlock($block);
        }
        return implode("\n", $html);
    }

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

        return $this->wrapEditorMode($html, $id, $type);
    }

    /**
     * In editor_mode, wraps block HTML with canvas selection overlay.
     * No-op in production rendering (editor_mode not set in context).
     */
    protected function wrapEditorMode(string $html, string $id, string $type): string
    {
        if (!($this->context['editor_mode'] ?? false)) {
            return $html;
        }
        $esc = htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $idEsc = htmlspecialchars($id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return "<div class=\"tb-canvas-block\" data-tb-block-id=\"{$idEsc}\" data-tb-block-type=\"{$esc}\">"
            . "<div class=\"tb-block-badge\">{$esc}</div>"
            . $html
            . "</div>";
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
        $children = $block['blocks'] ?? $block['children'] ?? [];

        $inner = '';
        if (in_array($type, ['section', 'container', 'grid', 'columns', 'row', 'column'], true) && !empty($children)) {
            $inner = $this->renderBlocks($children);
        }
        if ($type === 'tabs' && !empty($props['tabs'])) {
            $inner = $this->renderTabs($props['tabs']);
        }
        if ($type === 'accordion' && !empty($props['items'])) {
            $inner = $this->renderAccordion($props['items']);
        }
        if ($type === 'slider' && !empty($props['slides'])) {
            $inner = $this->renderBlocks($props['slides']);
        }

        if (\App\Builder\ElementRegistry::has($type)) {
            $context = array_merge($this->context, [
                'id' => $id,
                'inner' => $inner,
                'style' => $block['style'] ?? [],
            ]);
            return app(\App\Builder\Rendering\ElementRenderer::class)
                ->setContext($this->context)
                ->render($type, $props, $context);
        }

        $method = 'render' . Str::studly($type);
        if (method_exists($this, $method)) {
            return $this->{$method}($props, $inner, $id);
        }

        return $this->renderFallback($type, $props, $inner, $id);
    }

    protected function renderSection(array $props, string $inner, string $id): string
    {
        $padding = $this->spacingClass($props['padding'] ?? 'md');
        $bg = $this->escapeAttr($props['background'] ?? 'transparent');
        $bgImg = $this->escapeAttr($props['background_image'] ?? '');
        $useContainer = ($props['container'] ?? true);
        $inlineStyles = [];
        if ($bgImg) {
            $inlineStyles[] = "background-image: url({$bgImg})";
            $inlineStyles[] = "background-size: cover";
        }

        // رنگ پس‌زمینه سفارشی از style.background.color
        $customBg = $props['_style']['background']['color'] ?? null;
        if ($customBg) {
            $inlineStyles[] = "background-color:{$this->escapeAttr($customBg)}";
        }
        $class = "tb-section tb-padding-{$padding}";
        if ($bg !== 'transparent' && !str_starts_with($bg, '#')) {
            $class .= " tb-bg-{$bg}";
        }
        if ($bg !== 'transparent' && str_starts_with($bg, '#')) {
            $inlineStyles[] = "background-color:{$bg}";
        }

        // کلاس‌های radius / shadow / hover از style.layout و style.effects
        $layoutStyle = $props['_style']['layout'] ?? [];
        $effectsStyle = $props['_style']['effects'] ?? [];
        $radius = $layoutStyle['radius'] ?? null;
        if (in_array($radius, ['sm', 'md', 'lg'], true)) {
            $class .= " tb-radius-{$radius}";
        }
        $shadow = $layoutStyle['shadow'] ?? null;
        if (in_array($shadow, ['sm', 'md', 'lg'], true)) {
            $class .= " tb-shadow-{$shadow}";
        }
        if (($effectsStyle['hover'] ?? null) === 'lift') {
            $class .= " tb-hover-lift";
        }

        $styleAttr = '';
        if (!empty($inlineStyles)) {
            $styleAttr = ' style="' . implode(';', $inlineStyles) . '"';
        }
        $open = $useContainer ? '<div class="tb-container">' : '';
        $close = $useContainer ? '</div>' : '';
        return "<section id=\"tb-{$id}\" class=\"{$class}\"{$styleAttr}><div class=\"tb-section-inner\">{$open}{$inner}{$close}</div></section>";
    }

    protected function renderContainer(array $props, string $inner, string $id): string
    {
        $max = $this->escapeAttr($props['maxWidth'] ?? '7xl');
        return "<div id=\"tb-{$id}\" class=\"tb-container tb-max-{$max}\">{$inner}</div>";
    }

    protected function renderGrid(array $props, string $inner, string $id): string
    {
        $cols = (int) ($props['columns'] ?? 3);
        $gap = $this->spacingClass($props['gap'] ?? 'md');
        return "<div id=\"tb-{$id}\" class=\"tb-grid tb-grid-cols-{$cols} tb-gap-{$gap}\">{$inner}</div>";
    }

    /**
     * گرید محصولات فروشگاه (با استفاده از ماژول shop سایت مشتری).
     *
     * props:
     * - columns: تعداد ستون‌ها (پیش‌فرض 4)
     * - limit: تعداد محصولات (پیش‌فرض 8)
     */
    protected function renderProduct_grid(array $props, string $inner, string $id): string
    {
        $limit = (int) ($props['limit'] ?? 8);
        $columns = (int) ($props['columns'] ?? 4);

        $query = Product::query()->active();
        $products = $query->latest()->take($limit)->get();

        $colsClass = "tb-grid-cols-{$columns}";
        if ($products->isEmpty()) {
            $shopUrl = route('shop.index');
            $empty = "<div class=\"tb-product-grid-empty\"><p>هنوز محصولی در این بخش ثبت نشده است.</p><a href=\"{$shopUrl}\" class=\"tb-btn tb-btn-primary\">مشاهده فروشگاه</a></div>";
            return "<div id=\"tb-{$id}\" class=\"tb-grid {$colsClass} tb-gap-md tb-product-grid-wrap\">{$empty}</div>";
        }

        $cards = [];
        foreach ($products as $product) {
            $url = route('shop.show', $product->slug);
            $title = $this->escape($product->title);
            $price = CurrencyHelper::formatPrice($product->effective_price) . ' ' . CurrencyHelper::unitLabel();
            $img = $product->image ? \App\Helpers\ImageHelper::url($product->image) : '';
            $imgHtml = $img ? "<div class=\"tb-product-card-img\"><img src=\"{$this->escapeAttr($img)}\" alt=\"{$title}\" loading=\"lazy\"></div>" : "<div class=\"tb-product-card-img tb-product-card-noimg\">📦</div>";
            $cards[] = "<a href=\"{$url}\" class=\"tb-product-card\">{$imgHtml}<div class=\"tb-product-card-inner\"><h3 class=\"tb-product-title\">{$title}</h3><div class=\"tb-product-price\">{$price}</div></div></a>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-grid {$colsClass} tb-gap-md tb-product-grid-wrap\">" . implode('', $cards) . "</div>";
    }

    /**
     * Blog posts block: real posts when blog module is active.
     */
    protected function renderBlogPosts(array $props, string $inner, string $id): string
    {
        if (!\App\Helpers\SiteHelper::blogEnabled()) {
            return "<div id=\"tb-{$id}\" class=\"tb-blog-posts-empty\"><p class=\"tb-text\">ماژول وبلاگ غیرفعال است.</p></div>";
        }
        $limit = (int) ($props['limit'] ?? 6);
        $title = $this->escape($props['title'] ?? 'آخرین مطالب');
        $posts = Post::published()->latest('published_at')->take($limit)->get();
        if ($posts->isEmpty()) {
            return "<div id=\"tb-{$id}\" class=\"tb-blog-posts\"><h2 class=\"tb-heading\">{$title}</h2><div class=\"tb-blog-posts-empty\"><p class=\"tb-text\">هنوز مطلبی منتشر نشده است.</p><a href=\"" . $this->escapeAttr(route('blog.index')) . "\" class=\"tb-btn tb-btn-primary\">وبلاگ</a></div></div>";
        }
        $cards = [];
        foreach ($posts as $post) {
            $url = route('blog.show', $post->slug);
            $t = $this->escape($post->title);
            $excerpt = $this->escape(\Illuminate\Support\Str::limit(strip_tags($post->excerpt ?? $post->body ?? ''), 120));
            $date = $post->published_at ? \App\Helpers\Jalali::formatFa($post->published_at, 'Y/m/d') : '';
            $img = '';
            if (!empty($post->featured_image)) {
                $imgUrl = \App\Helpers\ImageHelper::url($post->featured_image);
                $img = "<div class=\"tb-blog-card-img\"><img src=\"" . $this->escapeAttr($imgUrl) . "\" alt=\"{$t}\" loading=\"lazy\"></div>";
            } else {
                $img = "<div class=\"tb-blog-card-img tb-blog-card-noimg\">📰</div>";
            }
            $cards[] = "<a href=\"{$url}\" class=\"tb-blog-card\">{$img}<div class=\"tb-blog-card-body\"><h3 class=\"tb-blog-card-title\">{$t}</h3><p class=\"tb-blog-card-meta\">{$date}</p><p class=\"tb-blog-card-excerpt\">{$excerpt}</p></div></a>";
        }
        $html = "<h2 class=\"tb-heading tb-section-title\">{$title}</h2><div class=\"tb-grid tb-grid-cols-3 tb-gap-lg tb-blog-grid\">" . implode('', $cards) . "</div>";
        return "<div id=\"tb-{$id}\" class=\"tb-blog-posts\">{$html}</div>";
    }

    /**
     * Testimonials / reviews block: real approved reviews when module active.
     */
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

    protected function renderColumns(array $props, string $inner, string $id): string
    {
        $ratios = $props['ratios'] ?? '1/2,1/2';
        $parts = array_map('trim', explode(',', $ratios));
        $classes = [];
        foreach ($parts as $i => $r) {
            $classes[] = "tb-col-" . ($i + 1);
        }
        $colClass = implode(' ', $classes);
        return "<div id=\"tb-{$id}\" class=\"tb-columns {$colClass}\">{$inner}</div>";
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

    protected function renderHero(array $props, string $inner, string $id): string
    {
        $title = $this->escape($props['title'] ?? '');
        $subtitle = $this->escape($props['subtitle'] ?? '');
        $btnText = $this->escape($props['button_text'] ?? '');
        $btnUrl = $this->escapeAttr($props['button_url'] ?? '#');
        $bg = $props['background'] ?? 'primary';
        $align = $props['align'] ?? 'center';
        $btn = $btnText ? "<a href=\"{$btnUrl}\" class=\"tb-btn tb-btn-primary\">{$btnText}</a>" : '';
        return "<section id=\"tb-{$id}\" class=\"tb-hero tb-bg-{$bg} tb-align-{$align}\"><div class=\"tb-hero-inner\"><h1 class=\"tb-hero-title\">{$title}</h1><p class=\"tb-hero-subtitle\">{$subtitle}</p>{$btn}</div></section>";
    }

    protected function renderHeading(array $props, string $inner, string $id): string
    {
        $level = max(1, min(6, (int) ($props['level'] ?? 2)));
        $text = $this->escape($props['text'] ?? '');
        $style = $props['_style']['typography'] ?? [];
        $inline = [];
        if (!empty($style['color'])) {
            $inline[] = 'color:' . $this->escapeAttr($style['color']);
        }
        if (!empty($style['textAlign'])) {
            $align = in_array($style['textAlign'], ['left', 'right', 'center'], true) ? $style['textAlign'] : 'inherit';
            $inline[] = "text-align:{$this->escapeAttr($align)}";
        }
        // هدینگ را همیشه روی عرض کامل ستون نگه می‌داریم
        $inline[] = 'display:block';
        $inline[] = 'width:100%';
        $styleAttr = $inline ? ' style="' . implode(';', $inline) . '"' : '';
        return "<h{$level} id=\"tb-{$id}\" class=\"tb-heading\"{$styleAttr}>{$text}</h{$level}>";
    }

    protected function renderText(array $props, string $inner, string $id): string
    {
        $content = $props['content'] ?? '';
        $content = $this->sanitizeHtml($content);
        $style = $props['_style']['typography'] ?? [];
        $wrapperStyles = [];
        $innerStyles = [];
        if (!empty($style['color'])) {
            $wrapperStyles[] = 'color:' . $this->escapeAttr($style['color']);
        }
        // تراز را هم روی div بیرونی و هم روی محتوای داخلی اعمال می‌کنیم تا روی هر نوع HTML مؤثر باشد
        if (!empty($style['textAlign'])) {
            $align = in_array($style['textAlign'], ['left', 'right', 'center'], true) ? $style['textAlign'] : 'inherit';
            $wrapperStyles[] = "text-align:{$this->escapeAttr($align)}";
            $innerStyles[] = "text-align:{$this->escapeAttr($align)}";
        }
        $wrapperAttr = $wrapperStyles ? ' style="' . implode(';', $wrapperStyles) . '"' : '';
        $innerAttr = $innerStyles ? ' style="' . implode(';', $innerStyles) . '"' : '';
        // محتوای متنی را داخل یک div داخلی می‌پیچیم تا تراز قطعاً روی متن اعمال شود
        return "<div id=\"tb-{$id}\" class=\"tb-text\"{$wrapperAttr}><div{$innerAttr}>{$content}</div></div>";
    }

    protected function renderImage(array $props, string $inner, string $id): string
    {
        $url = $this->escapeAttr($props['url'] ?? '');
        $alt = $this->escapeAttr($props['alt'] ?? '');
        $caption = $this->escape($props['caption'] ?? '');
        if ($url === '') {
            return "<figure id=\"tb-{$id}\" class=\"tb-image tb-image-placeholder\"><span>تصویر</span></figure>";
        }
        $img = "<img src=\"{$url}\" alt=\"{$alt}\" loading=\"lazy\" class=\"tb-img\">";
        $cap = $caption ? "<figcaption class=\"tb-image-caption\">{$caption}</figcaption>" : '';
        return "<figure id=\"tb-{$id}\" class=\"tb-image\">{$img}{$cap}</figure>";
    }

    protected function renderButton(array $props, string $inner, string $id): string
    {
        $text = $this->escape($props['text'] ?? '');
        $url = $this->escapeAttr($props['url'] ?? '#');
        $style = $props['style'] ?? 'primary';
        return "<a id=\"tb-{$id}\" href=\"{$url}\" class=\"tb-btn tb-btn-{$style}\">{$text}</a>";
    }

    protected function renderDivider(array $props, string $inner, string $id): string
    {
        return "<hr id=\"tb-{$id}\" class=\"tb-divider\">";
    }

    protected function renderSpacer(array $props, string $inner, string $id): string
    {
        $height = $props['height'] ?? 'md';
        $map = ['none' => '0', 'xs' => '0.5rem', 'sm' => '1rem', 'md' => '1.5rem', 'lg' => '2.5rem', 'xl' => '4rem'];
        $h = $map[$height] ?? '1.5rem';
        return "<div id=\"tb-{$id}\" class=\"tb-spacer\" style=\"height:{$h}\" aria-hidden=\"true\"></div>";
    }

    protected function renderNav_menu(array $props, string $inner, string $id): string
    {
        $location = $props['location'] ?? 'header';
        $menuHtml = $this->context['menu_html'] ?? '';
        return "<nav id=\"tb-{$id}\" class=\"tb-nav tb-nav-{$location}\" aria-label=\"منوی اصلی\">{$menuHtml}</nav>";
    }

    protected function renderSearchBar(array $props, string $inner, string $id): string
    {
        $placeholder = $this->escapeAttr($props['placeholder'] ?? 'جستجو...');
        $action = \App\Helpers\SiteHelper::shopEnabled() ? route('shop.index') : route('home');
        return "<div id=\"tb-{$id}\" class=\"tb-search-bar-wrap\"><form method=\"get\" action=\"{$action}\" role=\"search\"><input type=\"search\" name=\"q\" placeholder=\"{$placeholder}\" aria-label=\"جستجو\"></form></div>";
    }

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

    protected function renderSocial_icons(array $props, string $inner, string $id): string
    {
        $links = $props['links'] ?? [];
        if (empty($links) && !empty($this->context['social_links'])) {
            $links = $this->context['social_links'];
        }
        $items = [];
        foreach ($links as $link) {
            $url = $this->escapeAttr(is_array($link) ? ($link['url'] ?? '#') : $link);
            $label = $this->escape(is_array($link) ? ($link['label'] ?? '') : '');
            $items[] = "<a href=\"{$url}\" class=\"tb-social-link\" target=\"_blank\" rel=\"noopener noreferrer\" aria-label=\"{$label}\">{$label}</a>";
        }
        return "<div id=\"tb-{$id}\" class=\"tb-social-icons\">" . implode('', $items) . "</div>";
    }

    protected function renderCta(array $props, string $inner, string $id): string
    {
        $title = $this->escape($props['title'] ?? '');
        $subtitle = $this->escape($props['subtitle'] ?? '');
        $btnText = $this->escape($props['button_text'] ?? '');
        $btnUrl = $this->escapeAttr($props['button_url'] ?? '#');
        $bg = $props['background'] ?? 'primary';
        $btn = $btnText ? "<a href=\"{$btnUrl}\" class=\"tb-btn tb-btn-primary\">{$btnText}</a>" : '';
        return "<section id=\"tb-{$id}\" class=\"tb-cta tb-bg-{$bg}\"><h2 class=\"tb-cta-title\">{$title}</h2><p class=\"tb-cta-subtitle\">{$subtitle}</p>{$btn}</section>";
    }

    protected function renderPromoBanner(array $props, string $inner, string $id): string
    {
        $text = $this->escape($props['text'] ?? '');
        $link = $this->escapeAttr($props['link'] ?? '#');
        $variant = $this->escapeAttr($props['style'] ?? 'info');
        if ($text === '') {
            return '';
        }

        return "<div id=\"tb-{$id}\" class=\"tb-promo-banner tb-promo-{$variant}\"><a href=\"{$link}\" class=\"tb-promo-banner__link\">{$text}</a></div>";
    }

    protected function renderCountdown(array $props, string $inner, string $id): string
    {
        $label = $this->escape($props['label'] ?? '');
        $end = $this->escapeAttr($props['end_date'] ?? '');
        $labelHtml = $label ? "<span class=\"tb-countdown__label\">{$label}</span>" : '';
        $endHtml = $end ? "<span class=\"tb-countdown__end\" data-end=\"{$end}\"></span>" : '';

        return "<div id=\"tb-{$id}\" class=\"tb-countdown\" data-end-date=\"{$end}\">{$labelHtml}{$endHtml}<span class=\"tb-countdown__hint\">تاریخ پایان را در تنظیمات المان وارد کنید تا شمارش معکوس فعال شود.</span></div>";
    }

    protected function renderGallery(array $props, string $inner, string $id): string
    {
        $title = $this->escape($props['title'] ?? '');
        $items = $props['items'] ?? [];
        $h = $title ? "<h2 class=\"tb-heading tb-section-title\">{$title}</h2>" : '';
        $cells = [];
        foreach ($items as $item) {
            $url = $this->escapeAttr(is_array($item) ? ($item['url'] ?? '') : $item);
            $alt = $this->escape(is_array($item) ? ($item['alt'] ?? '') : '');
            if ($url === '') {
                continue;
            }
            $cells[] = "<div class=\"tb-gallery-cell\"><img src=\"{$url}\" alt=\"{$alt}\" loading=\"lazy\" class=\"tb-gallery-img\"></div>";
        }
        if ($cells === []) {
            return "<div id=\"tb-{$id}\" class=\"tb-gallery tb-gallery-empty\"><p class=\"tb-text\">تصاویر گالری را در تنظیمات المان اضافه کنید.</p></div>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-gallery\">{$h}<div class=\"tb-gallery-grid\">" . implode('', $cells) . '</div></div>';
    }

    protected function renderCustomHtml(array $props, string $inner, string $id): string
    {
        $raw = (string) ($props['content'] ?? '');
        $clean = strip_tags($raw, '<p><br><a><strong><em><ul><ol><li><h2><h3><h4><div><span><blockquote>');

        return "<div id=\"tb-{$id}\" class=\"tb-custom-html\">{$clean}</div>";
    }

    protected function renderTabs(array $tabs): string
    {
        $out = [];
        foreach ($tabs as $i => $tab) {
            $title = $this->escape($tab['title'] ?? "تب " . ($i + 1));
            $blocks = $tab['blocks'] ?? [];
            $content = $this->renderBlocks($blocks);
            $out[] = "<div class=\"tb-tab-panel\" data-tab=\"{$i}\"><h3 class=\"tb-tab-title\">{$title}</h3><div class=\"tb-tab-content\">{$content}</div></div>";
        }
        return "<div class=\"tb-tabs\">" . implode('', $out) . "</div>";
    }

    protected function renderAccordion(array $items): string
    {
        $out = [];
        foreach ($items as $i => $item) {
            $title = $this->escape($item['title'] ?? '');
            $content = $this->renderBlocks($item['blocks'] ?? []);
            $out[] = "<details class=\"tb-accordion-item\"><summary>{$title}</summary><div class=\"tb-accordion-body\">{$content}</div></details>";
        }
        return "<div class=\"tb-accordion\">" . implode('', $out) . "</div>";
    }

    // Alias so Str::studly('product_grid') → renderProductGrid works
    protected function renderProductGrid(array $props, string $inner, string $id): string
    {
        return $this->renderProduct_grid($props, $inner, $id);
    }

    // Alias so Str::studly('blog_posts') falls through correctly
    protected function renderBlog_posts(array $props, string $inner, string $id): string
    {
        return $this->renderBlogPosts($props, $inner, $id);
    }

    // Alias so Str::studly('social_icons') → renderSocialIcons works
    protected function renderSocialIcons(array $props, string $inner, string $id): string
    {
        return $this->renderSocial_icons($props, $inner, $id);
    }

    // Alias so Str::studly('nav_menu') → renderNavMenu works
    protected function renderNavMenu(array $props, string $inner, string $id): string
    {
        return $this->renderNav_menu($props, $inner, $id);
    }

    // Alias so Str::studly('custom_html') → renderCustomHtml works
    protected function renderCustomHtml2(array $props, string $inner, string $id): string
    {
        return $this->renderCustomHtml($props, $inner, $id);
    }

    // Alias so Str::studly('promo_banner') → renderPromoBanner works
    protected function renderPromo_banner(array $props, string $inner, string $id): string
    {
        return $this->renderPromoBanner($props, $inner, $id);
    }

    // Alias so Str::studly('search_bar') → renderSearchBar works
    protected function renderSearch_bar(array $props, string $inner, string $id): string
    {
        return $this->renderSearchBar($props, $inner, $id);
    }

    // Alias so Str::studly('cart_icon') → renderCartIcon works
    protected function renderCart_icon(array $props, string $inner, string $id): string
    {
        return $this->renderCartIcon($props, $inner, $id);
    }

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

        if (!class_exists(\App\Models\Course::class)) {
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

        if (!class_exists(\App\Models\ProductCategory::class)) {
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

    protected function spacingClass(string $v): string
    {
        $map = ['none' => 'none', 'xs' => 'xs', 'sm' => 'sm', 'md' => 'md', 'lg' => 'lg', 'xl' => 'xl'];
        return $map[$v] ?? 'md';
    }

    protected function escape(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    protected function escapeAttr(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    protected function sanitizeHtml(string $s): string
    {
        return strip_tags($s, '<p><br><a><strong><em><ul><ol><li><h2><h3>');
    }
}

