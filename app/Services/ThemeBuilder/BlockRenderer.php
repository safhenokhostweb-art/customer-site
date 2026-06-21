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

        $html = $this->applyBlockStyle($html, $block['style'] ?? [], $block['responsive'] ?? [], $id);
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
        $esc   = htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $idEsc = htmlspecialchars($id,   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        // TB4: mark container blocks so iframe can show nested drop zones
        $containerAttr = in_array($type, ['section', 'container', 'grid', 'columns', 'row', 'column', 'flex_container'], true)
            ? ' data-tb-container="1"' : '';
        return "<div class=\"tb-canvas-block\" data-tb-block-id=\"{$idEsc}\" data-tb-block-type=\"{$esc}\"{$containerAttr}>"
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
        // TB4: children may be at top-level (block.children), legacy (block.blocks), or in props.blocks
        $children = $block['children'] ?? $block['blocks'] ?? ($props['blocks'] ?? []);

        $inner = '';
        $containerTypes = ['section', 'container', 'grid', 'columns', 'row', 'column', 'flex_container'];
        if (in_array($type, $containerTypes, true) && !empty($children)) {
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

    protected function renderFlex_container(array $props, string $inner, string $id): string
    {
        $allowDir  = ['column', 'row', 'column-reverse', 'row-reverse'];
        $direction = in_array($props['direction'] ?? 'column', $allowDir, true) ? $props['direction'] : 'column';
        $gapMap    = ['none' => '0', 'xs' => '4px', 'sm' => '8px', 'md' => '16px', 'lg' => '24px', 'xl' => '40px'];
        $gap       = $gapMap[$props['gap'] ?? 'md'] ?? '16px';
        $wrap      = ($props['wrap'] ?? false) ? 'wrap' : 'nowrap';
        $allowAlign = ['stretch', 'flex-start', 'center', 'flex-end', 'baseline'];
        $align      = in_array($props['alignItems'] ?? 'stretch', $allowAlign, true) ? $props['alignItems'] : 'stretch';
        $allowJust  = ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'];
        $justify    = in_array($props['justifyContent'] ?? 'flex-start', $allowJust, true) ? $props['justifyContent'] : 'flex-start';
        $minH       = max(0, (int)($props['minHeight'] ?? 0));
        $minHStyle  = $minH > 0 ? "min-height:{$minH}px;" : '';
        $empty      = '';
        if (empty(trim($inner)) && ($this->context['editor_mode'] ?? false)) {
            $empty = '<div class="tb-fc-empty"><span>کانتینر خالی — بلوک اضافه کنید</span></div>';
        }
        $style = "display:flex;flex-direction:{$direction};gap:{$gap};flex-wrap:{$wrap};align-items:{$align};justify-content:{$justify};{$minHStyle}";
        return "<div id=\"tb-{$id}\" class=\"tb-flex-container\" style=\"{$style}\">{$inner}{$empty}</div>";
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
        $eaTitle = $this->editableAttr('title');
        $eaSub   = $this->editableAttr('subtitle');
        $eaBtn   = $this->editableAttr('button_text');
        $btn = $btnText ? "<a href=\"{$btnUrl}\" class=\"tb-btn tb-btn-primary\"{$eaBtn}>{$btnText}</a>" : '';
        return "<section id=\"tb-{$id}\" class=\"tb-hero tb-bg-{$bg} tb-align-{$align}\"><div class=\"tb-hero-inner\"><h1 class=\"tb-hero-title\"{$eaTitle}>{$title}</h1><p class=\"tb-hero-subtitle\"{$eaSub}>{$subtitle}</p>{$btn}</div></section>";
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
        $ea = $this->editableAttr('text');
        return "<h{$level} id=\"tb-{$id}\" class=\"tb-heading\"{$styleAttr}{$ea}>{$text}</h{$level}>";
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
        $ea = $this->editableAttr('content', 'html');
        // محتوای متنی را داخل یک div داخلی می‌پیچیم تا تراز قطعاً روی متن اعمال شود
        return "<div id=\"tb-{$id}\" class=\"tb-text\"{$wrapperAttr}><div{$innerAttr}{$ea}>{$content}</div></div>";
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
        $eaCap = $this->editableAttr('caption');
        $cap = $caption ? "<figcaption class=\"tb-image-caption\"{$eaCap}>{$caption}</figcaption>" : '';
        return "<figure id=\"tb-{$id}\" class=\"tb-image\">{$img}{$cap}</figure>";
    }

    protected function renderButton(array $props, string $inner, string $id): string
    {
        $text = $this->escape($props['text'] ?? '');
        $url = $this->escapeAttr($props['url'] ?? '#');
        $style = $props['style'] ?? 'primary';
        $ea = $this->editableAttr('text');
        return "<a id=\"tb-{$id}\" href=\"{$url}\" class=\"tb-btn tb-btn-{$style}\"{$ea}>{$text}</a>";
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
        $eaTitle = $this->editableAttr('title');
        $eaSub   = $this->editableAttr('subtitle');
        $eaBtn   = $this->editableAttr('button_text');
        $btn = $btnText ? "<a href=\"{$btnUrl}\" class=\"tb-btn tb-btn-primary\"{$eaBtn}>{$btnText}</a>" : '';
        return "<section id=\"tb-{$id}\" class=\"tb-cta tb-bg-{$bg}\"><h2 class=\"tb-cta-title\"{$eaTitle}>{$title}</h2><p class=\"tb-cta-subtitle\"{$eaSub}>{$subtitle}</p>{$btn}</section>";
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

    // ── HVAC / Industry-specific block renderers ─────────────────────────

    protected function renderNavHeader(array $props, string $inner, string $id): string
    {
        $logoText = $this->escape($props['logo_text'] ?? 'Acool HVAC');
        $phone    = $this->escape($props['phone'] ?? '');
        $ctaText  = $this->escape($props['cta_text'] ?? 'Book Service');
        $ctaUrl   = $this->escapeAttr($props['cta_url'] ?? '#contact');
        $links    = is_array($props['links'] ?? null) ? $props['links'] : [];
        $sticky   = ($props['sticky'] ?? true) ? 'position:sticky;top:0;' : '';

        $navLinks = '';
        foreach ($links as $link) {
            $href  = $this->escapeAttr(is_array($link) ? ($link['url'] ?? '#') : '#');
            $label = $this->escape(is_array($link) ? ($link['label'] ?? '') : (string)$link);
            $navLinks .= "<a href=\"{$href}\" style=\"color:#374151;font-weight:500;text-decoration:none;font-size:0.9375rem;transition:color 0.2s;\">{$label}</a>";
        }

        $phoneSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="#0ea5e9"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>';
        $phoneHtml = $phone
            ? "<a href=\"tel:{$this->escapeAttr(preg_replace('/[^+\d]/', '', $phone))}\" style=\"display:flex;align-items:center;gap:0.4rem;color:#1e3a5f;font-weight:600;text-decoration:none;\">{$phoneSvg}{$phone}</a>"
            : '';

        $logoSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>';

        return "<nav id=\"tb-{$id}\" style=\"{$sticky}background:#ffffff;box-shadow:0 2px 12px rgba(0,0,0,0.08);z-index:999;\">
<div style=\"max-width:1200px;margin:0 auto;padding:0 1.5rem;display:flex;align-items:center;justify-content:space-between;min-height:70px;\">
  <a href=\"/\" style=\"font-size:1.4rem;font-weight:800;color:#1e3a5f;text-decoration:none;display:flex;align-items:center;gap:0.5rem;\">
    <span style=\"display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;background:linear-gradient(135deg,#1e3a5f,#0ea5e9);border-radius:8px;\">{$logoSvg}</span>
    {$logoText}
  </a>
  <div style=\"display:flex;align-items:center;gap:2rem;\">{$navLinks}</div>
  <div style=\"display:flex;align-items:center;gap:1.5rem;\">
    {$phoneHtml}
    <a href=\"{$ctaUrl}\" style=\"background:#0ea5e9;color:#fff;padding:0.625rem 1.4rem;border-radius:6px;font-weight:600;text-decoration:none;font-size:0.9rem;white-space:nowrap;\">{$ctaText}</a>
  </div>
</div>
</nav>";
    }

    protected function renderNav_header(array $props, string $inner, string $id): string
    {
        return $this->renderNavHeader($props, $inner, $id);
    }

    protected function renderHvacHero(array $props, string $inner, string $id): string
    {
        $badge    = $this->escape($props['badge'] ?? 'Professional HVAC Services');
        $title    = $this->escape($props['title'] ?? 'Expert Air Conditioning & HVAC Repair Services');
        $subtitle = $this->escape($props['subtitle'] ?? 'Trusted by thousands of homeowners. 24/7 emergency service available.');
        $btn1Text = $this->escape($props['btn_primary_text'] ?? 'Get Free Quote');
        $btn1Url  = $this->escapeAttr($props['btn_primary_url'] ?? '#contact');
        $btn2Text = $this->escape($props['btn_secondary_text'] ?? 'Our Services');
        $btn2Url  = $this->escapeAttr($props['btn_secondary_url'] ?? '#services');
        $phone    = $this->escape($props['phone'] ?? '');
        $rating   = $this->escape($props['rating'] ?? '4.9');
        $ratingLbl = $this->escape($props['rating_label'] ?? '500+ Reviews');

        $stars = '<span style="color:#f59e0b;font-size:1rem;">★★★★★</span>';
        $ratingHtml = "<div style=\"display:flex;align-items:center;gap:0.6rem;\">{$stars}<span style=\"color:rgba(255,255,255,0.9);font-size:0.875rem;\">{$rating}/5.0 · {$ratingLbl}</span></div>";
        $phoneHtml  = $phone ? "<div style=\"display:flex;align-items:center;gap:0.5rem;color:rgba(255,255,255,0.85);font-size:0.95rem;\"><span style=\"color:#0ea5e9;\">&#9990;</span> Emergency: {$phone}</div>" : '';

        $techSvg = '<svg viewBox="0 0 280 320" width="280" height="320" xmlns="http://www.w3.org/2000/svg">
  <circle cx="140" cy="70" r="50" fill="#0ea5e9" opacity="0.15"/>
  <circle cx="140" cy="65" r="35" fill="#dbeafe"/>
  <ellipse cx="140" cy="170" rx="65" ry="80" fill="#1e40af"/>
  <rect x="105" y="120" width="70" height="15" rx="3" fill="#2563eb"/>
  <text x="140" y="220" text-anchor="middle" fill="white" font-size="12" font-family="sans-serif">HVAC TECH</text>
  <rect x="80" y="200" width="35" height="50" rx="4" fill="#1d4ed8"/>
  <rect x="165" y="200" width="35" height="50" rx="4" fill="#1d4ed8"/>
  <rect x="60" y="130" width="25" height="60" rx="4" fill="#2563eb"/>
  <rect x="195" y="130" width="25" height="60" rx="4" fill="#2563eb"/>
  <rect x="170" y="100" width="45" height="35" rx="6" fill="#0ea5e9" opacity="0.8"/>
  <text x="192" y="122" text-anchor="middle" fill="white" font-size="10">AC</text>
</svg>';

        $bgPattern = "background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 50%,#1e40af 100%)";

        return "<section id=\"tb-{$id}\" style=\"{$bgPattern};padding:5rem 0;overflow:hidden;position:relative;\">
<div style=\"position:absolute;inset:0;opacity:0.05;background-image:radial-gradient(circle at 2px 2px,white 1px,transparent 0);background-size:32px 32px;\"></div>
<div style=\"max-width:1200px;margin:0 auto;padding:0 1.5rem;display:flex;align-items:center;gap:4rem;position:relative;z-index:1;\">
  <div style=\"flex:1;min-width:0;\">
    <div style=\"display:inline-flex;align-items:center;gap:0.5rem;background:rgba(14,165,233,0.15);border:1px solid rgba(14,165,233,0.3);color:#7dd3fc;padding:0.4rem 1rem;border-radius:999px;font-size:0.8rem;font-weight:600;letter-spacing:0.05em;margin-bottom:1.5rem;\">&check; {$badge}</div>
    <h1 style=\"font-size:clamp(2rem,3.5vw,2.875rem);font-weight:800;color:#ffffff;line-height:1.15;margin:0 0 1.25rem;\">{$title}</h1>
    <p style=\"font-size:1.0625rem;color:rgba(255,255,255,0.75);line-height:1.7;margin:0 0 2rem;\">{$subtitle}</p>
    <div style=\"display:flex;flex-wrap:wrap;gap:1rem;margin-bottom:2rem;\">
      <a href=\"{$btn1Url}\" style=\"background:#0ea5e9;color:#fff;padding:0.875rem 2rem;border-radius:8px;font-weight:700;text-decoration:none;font-size:1rem;box-shadow:0 4px 15px rgba(14,165,233,0.4);\">{$btn1Text}</a>
      <a href=\"{$btn2Url}\" style=\"background:transparent;color:#fff;padding:0.875rem 2rem;border-radius:8px;font-weight:600;text-decoration:none;font-size:1rem;border:2px solid rgba(255,255,255,0.4);\">{$btn2Text}</a>
    </div>
    <div style=\"display:flex;flex-direction:column;gap:0.6rem;\">
      {$ratingHtml}
      {$phoneHtml}
    </div>
  </div>
  <div style=\"flex-shrink:0;display:flex;align-items:center;justify-content:center;\">
    <div style=\"background:rgba(255,255,255,0.05);border:2px solid rgba(14,165,233,0.3);border-radius:20px;padding:2rem;backdrop-filter:blur(4px);\">
      {$techSvg}
    </div>
  </div>
</div>
</section>";
    }

    protected function renderHvac_hero(array $props, string $inner, string $id): string
    {
        return $this->renderHvacHero($props, $inner, $id);
    }

    protected function renderClientLogos(array $props, string $inner, string $id): string
    {
        $title = $this->escape($props['title'] ?? 'Our Trusted Partners');
        $logos = is_array($props['logos'] ?? null) ? $props['logos'] : [];

        if (empty($logos)) {
            $logos = [
                ['name' => 'Carrier'], ['name' => 'Trane'], ['name' => 'Lennox'],
                ['name' => 'Daikin'], ['name' => 'Mitsubishi'], ['name' => 'Rheem'],
                ['name' => 'York'], ['name' => 'Goodman'], ['name' => 'Honeywell'],
            ];
        }

        $items = '';
        foreach ($logos as $logo) {
            $name = $this->escape(is_array($logo) ? ($logo['name'] ?? '') : (string)$logo);
            $items .= "<div style=\"display:flex;align-items:center;justify-content:center;padding:0.75rem 1.5rem;border:1px solid #e5e7eb;border-radius:8px;min-width:100px;background:#fff;\">
                <span style=\"font-weight:700;color:#6b7280;font-size:0.875rem;letter-spacing:0.05em;\">{$name}</span>
            </div>";
        }

        return "<section id=\"tb-{$id}\" style=\"background:#f8fafc;padding:3rem 0;border-top:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb;\">
<div style=\"max-width:1200px;margin:0 auto;padding:0 1.5rem;\">
  <p style=\"text-align:center;color:#9ca3af;font-size:0.875rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;margin:0 0 1.5rem;\">{$title}</p>
  <div style=\"display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:1rem;\">{$items}</div>
</div>
</section>";
    }

    protected function renderClient_logos(array $props, string $inner, string $id): string
    {
        return $this->renderClientLogos($props, $inner, $id);
    }

    protected function renderAboutSection(array $props, string $inner, string $id): string
    {
        $badge   = $this->escape($props['badge'] ?? 'About Us');
        $title   = $this->escape($props['title'] ?? 'We Are Your Trusted HVAC Experts');
        $desc    = $this->sanitizeHtml($props['description'] ?? 'With over 15 years of experience, Acool HVAC provides top-quality air conditioning and heating solutions for residential and commercial clients.');
        $btnText = $this->escape($props['btn_text'] ?? 'Learn More');
        $btnUrl  = $this->escapeAttr($props['btn_url'] ?? '#');
        $features = is_array($props['features'] ?? null) ? $props['features'] : [];
        $stats    = is_array($props['stats'] ?? null) ? $props['stats'] : [];

        $featuresHtml = '';
        foreach ($features as $f) {
            $label = $this->escape(is_array($f) ? ($f['label'] ?? (string)$f) : (string)$f);
            $featuresHtml .= "<div style=\"display:flex;align-items:flex-start;gap:0.75rem;margin-bottom:0.75rem;\">
                <span style=\"display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;background:#0ea5e9;border-radius:50%;color:#fff;font-size:0.7rem;flex-shrink:0;margin-top:0.1rem;\">&#10003;</span>
                <span style=\"color:#374151;font-size:0.9375rem;line-height:1.5;\">{$label}</span>
            </div>";
        }

        $statsHtml = '';
        foreach ($stats as $s) {
            $val   = $this->escape(is_array($s) ? ($s['value'] ?? '') : '');
            $label = $this->escape(is_array($s) ? ($s['label'] ?? '') : '');
            $statsHtml .= "<div style=\"text-align:center;padding:1rem;\">
                <div style=\"font-size:1.75rem;font-weight:800;color:#1e3a5f;\">{$val}</div>
                <div style=\"font-size:0.8rem;color:#6b7280;margin-top:0.2rem;\">{$label}</div>
            </div>";
        }
        $statsBlock = $statsHtml ? "<div style=\"display:flex;flex-wrap:wrap;gap:0.5rem;border-top:1px solid #e5e7eb;margin-top:1.5rem;padding-top:1.5rem;\">{$statsHtml}</div>" : '';

        $imgBg = "background:linear-gradient(135deg,#dbeafe 0%,#bfdbfe 100%)";
        $imgSvg = '<svg viewBox="0 0 400 320" width="100%" xmlns="http://www.w3.org/2000/svg">
  <rect width="400" height="320" fill="#dbeafe" rx="16"/>
  <rect x="60" y="60" width="280" height="200" rx="12" fill="#bfdbfe"/>
  <rect x="100" y="90" width="200" height="140" rx="8" fill="#93c5fd"/>
  <text x="200" y="170" text-anchor="middle" fill="#1e40af" font-size="14" font-weight="700" font-family="sans-serif">HVAC SYSTEM</text>
  <circle cx="200" cy="135" r="25" fill="#1e40af" opacity="0.2"/>
  <text x="200" y="140" text-anchor="middle" fill="#1e40af" font-size="22">❄️</text>
</svg>';

        return "<section id=\"tb-{$id}\" style=\"background:#ffffff;padding:5rem 0;\">
<div style=\"max-width:1200px;margin:0 auto;padding:0 1.5rem;display:flex;align-items:center;gap:4rem;flex-wrap:wrap;\">
  <div style=\"flex:1;min-width:280px;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.1);\">{$imgSvg}</div>
  <div style=\"flex:1;min-width:280px;\">
    <div style=\"display:inline-block;background:#eff6ff;color:#0ea5e9;padding:0.35rem 0.9rem;border-radius:999px;font-size:0.8rem;font-weight:700;letter-spacing:0.05em;margin-bottom:1rem;\">{$badge}</div>
    <h2 style=\"font-size:clamp(1.5rem,2.5vw,2.25rem);font-weight:800;color:#1e3a5f;line-height:1.2;margin:0 0 1rem;\">{$title}</h2>
    <div style=\"color:#4b5563;line-height:1.7;margin-bottom:1.25rem;\">{$desc}</div>
    {$featuresHtml}
    <a href=\"{$btnUrl}\" style=\"display:inline-block;margin-top:1.25rem;background:#0ea5e9;color:#fff;padding:0.75rem 1.75rem;border-radius:8px;font-weight:700;text-decoration:none;\">{$btnText}</a>
    {$statsBlock}
  </div>
</div>
</section>";
    }

    protected function renderAbout_section(array $props, string $inner, string $id): string
    {
        return $this->renderAboutSection($props, $inner, $id);
    }

    protected function renderServiceCards(array $props, string $inner, string $id): string
    {
        $title    = $this->escape($props['title'] ?? 'Our Services');
        $subtitle = $this->escape($props['subtitle'] ?? 'Comprehensive HVAC solutions for every need');
        $cols     = max(2, min(4, (int)($props['columns'] ?? 3)));
        $cards    = is_array($props['cards'] ?? null) ? $props['cards'] : [];

        $cardItems = '';
        foreach ($cards as $card) {
            $name = $this->escape(is_array($card) ? ($card['title'] ?? '') : '');
            $desc = $this->escape(is_array($card) ? ($card['description'] ?? '') : '');
            $icon = $this->escape(is_array($card) ? ($card['icon'] ?? '❄️') : '❄️');
            $url  = $this->escapeAttr(is_array($card) ? ($card['url'] ?? '#') : '#');
            $cardItems .= "<a href=\"{$url}\" style=\"display:block;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:2rem;text-decoration:none;transition:box-shadow 0.2s,transform 0.2s;box-shadow:0 2px 8px rgba(0,0,0,0.04);\">
                <div style=\"display:flex;align-items:center;justify-content:center;width:56px;height:56px;background:#eff6ff;border-radius:12px;font-size:1.75rem;margin-bottom:1rem;\">{$icon}</div>
                <h3 style=\"font-size:1rem;font-weight:700;color:#1e3a5f;margin:0 0 0.5rem;\">{$name}</h3>
                <p style=\"font-size:0.875rem;color:#6b7280;line-height:1.6;margin:0;\">{$desc}</p>
                <div style=\"margin-top:1rem;color:#0ea5e9;font-size:0.8rem;font-weight:600;\">Learn More →</div>
            </a>";
        }

        $gridStyle = "display:grid;grid-template-columns:repeat({$cols},1fr);gap:1.5rem;";

        return "<section id=\"tb-{$id}\" style=\"background:#f8fafc;padding:5rem 0;\">
<div style=\"max-width:1200px;margin:0 auto;padding:0 1.5rem;\">
  <div style=\"text-align:center;margin-bottom:3rem;\">
    <h2 style=\"font-size:clamp(1.5rem,2.5vw,2.25rem);font-weight:800;color:#1e3a5f;margin:0 0 0.75rem;\">{$title}</h2>
    <p style=\"color:#6b7280;font-size:1rem;margin:0;\">{$subtitle}</p>
  </div>
  <div style=\"{$gridStyle}\">{$cardItems}</div>
</div>
</section>";
    }

    protected function renderService_cards(array $props, string $inner, string $id): string
    {
        return $this->renderServiceCards($props, $inner, $id);
    }

    protected function renderStatsRow(array $props, string $inner, string $id): string
    {
        $title    = $this->escape($props['title'] ?? 'Why Choose Acool?');
        $subtitle = $this->escape($props['subtitle'] ?? 'Delivering excellence in HVAC services for over 15 years');
        $bg       = $this->escapeAttr($props['background'] ?? '#1e3a5f');
        $stats    = is_array($props['stats'] ?? null) ? $props['stats'] : [];

        $items = '';
        foreach ($stats as $stat) {
            $val   = $this->escape(is_array($stat) ? ($stat['value'] ?? '') : '');
            $label = $this->escape(is_array($stat) ? ($stat['label'] ?? '') : '');
            $icon  = $this->escape(is_array($stat) ? ($stat['icon'] ?? '') : '');
            $items .= "<div style=\"text-align:center;padding:1.5rem;\">
                <div style=\"font-size:2.5rem;margin-bottom:0.5rem;\">{$icon}</div>
                <div style=\"font-size:2.25rem;font-weight:800;color:#ffffff;line-height:1;\">{$val}</div>
                <div style=\"font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.4rem;\">{$label}</div>
            </div>";
        }

        $divider = "<div style=\"width:1px;background:rgba(255,255,255,0.15);height:80px;\"></div>";
        $finalItems = '';
        $statArr = array_values($stats);
        foreach ($statArr as $i => $stat) {
            $val   = $this->escape(is_array($stat) ? ($stat['value'] ?? '') : '');
            $label = $this->escape(is_array($stat) ? ($stat['label'] ?? '') : '');
            $icon  = $this->escape(is_array($stat) ? ($stat['icon'] ?? '') : '');
            if ($i > 0) $finalItems .= $divider;
            $finalItems .= "<div style=\"text-align:center;padding:1.5rem 2rem;flex:1;\">
                <div style=\"font-size:2rem;margin-bottom:0.5rem;\">{$icon}</div>
                <div style=\"font-size:2.25rem;font-weight:800;color:#ffffff;line-height:1;\">{$val}</div>
                <div style=\"font-size:0.875rem;color:rgba(255,255,255,0.7);margin-top:0.4rem;\">{$label}</div>
            </div>";
        }

        $bgStyle = str_starts_with($bg, '#') ? "background:{$bg}" : "background:linear-gradient(135deg,#1e3a5f,#1e40af)";

        return "<section id=\"tb-{$id}\" style=\"{$bgStyle};padding:4.5rem 0;position:relative;overflow:hidden;\">
<div style=\"position:absolute;inset:0;opacity:0.04;background-image:radial-gradient(circle at 2px 2px,white 1px,transparent 0);background-size:28px 28px;\"></div>
<div style=\"max-width:1200px;margin:0 auto;padding:0 1.5rem;position:relative;z-index:1;\">
  <div style=\"text-align:center;margin-bottom:3rem;\">
    <h2 style=\"font-size:clamp(1.5rem,2.5vw,2.25rem);font-weight:800;color:#ffffff;margin:0 0 0.75rem;\">{$title}</h2>
    <p style=\"color:rgba(255,255,255,0.7);font-size:1rem;margin:0;\">{$subtitle}</p>
  </div>
  <div style=\"display:flex;align-items:center;justify-content:center;flex-wrap:wrap;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:16px;\">{$finalItems}</div>
</div>
</section>";
    }

    protected function renderStats_row(array $props, string $inner, string $id): string
    {
        return $this->renderStatsRow($props, $inner, $id);
    }

    protected function renderTrustBadges(array $props, string $inner, string $id): string
    {
        $title    = $this->escape($props['title'] ?? 'Why Choose Us');
        $subtitle = $this->escape($props['subtitle'] ?? 'We deliver quality and reliability in every job');
        $cols     = max(2, min(4, (int)($props['columns'] ?? 3)));
        $items    = is_array($props['items'] ?? null) ? $props['items'] : [];

        $cards = '';
        foreach ($items as $item) {
            $name = $this->escape(is_array($item) ? ($item['title'] ?? '') : '');
            $desc = $this->escape(is_array($item) ? ($item['description'] ?? '') : '');
            $icon = $this->escape(is_array($item) ? ($item['icon'] ?? '✓') : '✓');
            $cards .= "<div style=\"background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:1.75rem;text-align:center;\">
                <div style=\"display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;background:#eff6ff;border-radius:12px;font-size:1.6rem;margin:0 auto 1rem;\">{$icon}</div>
                <h3 style=\"font-size:1rem;font-weight:700;color:#1e3a5f;margin:0 0 0.5rem;\">{$name}</h3>
                <p style=\"font-size:0.875rem;color:#6b7280;line-height:1.6;margin:0;\">{$desc}</p>
            </div>";
        }

        return "<section id=\"tb-{$id}\" style=\"background:#f8fafc;padding:5rem 0;\">
<div style=\"max-width:1200px;margin:0 auto;padding:0 1.5rem;\">
  <div style=\"text-align:center;margin-bottom:3rem;\">
    <h2 style=\"font-size:clamp(1.5rem,2.5vw,2.25rem);font-weight:800;color:#1e3a5f;margin:0 0 0.75rem;\">{$title}</h2>
    <p style=\"color:#6b7280;font-size:1rem;margin:0;\">{$subtitle}</p>
  </div>
  <div style=\"display:grid;grid-template-columns:repeat({$cols},1fr);gap:1.5rem;\">{$cards}</div>
</div>
</section>";
    }

    protected function renderTrust_badges(array $props, string $inner, string $id): string
    {
        return $this->renderTrustBadges($props, $inner, $id);
    }

    protected function renderProjectCards(array $props, string $inner, string $id): string
    {
        $title    = $this->escape($props['title'] ?? 'Our Projects');
        $subtitle = $this->escape($props['subtitle'] ?? 'Take a look at some of our completed HVAC installations');
        $cols     = max(2, min(4, (int)($props['columns'] ?? 2)));
        $cards    = is_array($props['cards'] ?? null) ? $props['cards'] : [];

        $bgColors = ['#dbeafe', '#dcfce7', '#fef3c7', '#fce7f3', '#e0e7ff', '#f0fdf4'];
        $cardItems = '';
        foreach ($cards as $i => $card) {
            $name  = $this->escape(is_array($card) ? ($card['title'] ?? '') : '');
            $desc  = $this->escape(is_array($card) ? ($card['description'] ?? '') : '');
            $cat   = $this->escape(is_array($card) ? ($card['category'] ?? '') : '');
            $url   = $this->escapeAttr(is_array($card) ? ($card['url'] ?? '#') : '#');
            $imgBg = $bgColors[$i % count($bgColors)];
            $cardItems .= "<div style=\"background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.06);\">
                <div style=\"height:200px;background:{$imgBg};display:flex;align-items:center;justify-content:center;font-size:3rem;\">🏗️</div>
                <div style=\"padding:1.5rem;\">
                    " . ($cat ? "<span style=\"background:#eff6ff;color:#0ea5e9;padding:0.25rem 0.75rem;border-radius:999px;font-size:0.75rem;font-weight:600;\">{$cat}</span>" : '') . "
                    <h3 style=\"font-size:1.0625rem;font-weight:700;color:#1e3a5f;margin:0.75rem 0 0.4rem;\">{$name}</h3>
                    <p style=\"font-size:0.875rem;color:#6b7280;margin:0 0 1rem;line-height:1.5;\">{$desc}</p>
                    <a href=\"{$url}\" style=\"color:#0ea5e9;font-size:0.875rem;font-weight:600;text-decoration:none;\">View Project →</a>
                </div>
            </div>";
        }

        return "<section id=\"tb-{$id}\" style=\"background:#ffffff;padding:5rem 0;\">
<div style=\"max-width:1200px;margin:0 auto;padding:0 1.5rem;\">
  <div style=\"text-align:center;margin-bottom:3rem;\">
    <h2 style=\"font-size:clamp(1.5rem,2.5vw,2.25rem);font-weight:800;color:#1e3a5f;margin:0 0 0.75rem;\">{$title}</h2>
    <p style=\"color:#6b7280;font-size:1rem;margin:0;\">{$subtitle}</p>
  </div>
  <div style=\"display:grid;grid-template-columns:repeat({$cols},1fr);gap:1.5rem;\">{$cardItems}</div>
</div>
</section>";
    }

    protected function renderProject_cards(array $props, string $inner, string $id): string
    {
        return $this->renderProjectCards($props, $inner, $id);
    }

    protected function renderContactInfo(array $props, string $inner, string $id): string
    {
        $title    = $this->escape($props['title'] ?? 'Contact Us');
        $subtitle = $this->escape($props['subtitle'] ?? 'Get in touch — we\'re here to help 24/7');
        $bg       = $this->escapeAttr($props['background'] ?? '#1e3a5f');
        $items    = is_array($props['items'] ?? null) ? $props['items'] : [];

        $bgStyle = str_starts_with($bg, '#') ? "background:{$bg}" : "background:linear-gradient(135deg,#0f172a,#1e3a5f)";

        $cardItems = '';
        foreach ($items as $item) {
            $label = $this->escape(is_array($item) ? ($item['label'] ?? '') : '');
            $value = $this->escape(is_array($item) ? ($item['value'] ?? '') : '');
            $icon  = $this->escape(is_array($item) ? ($item['icon'] ?? '📌') : '📌');
            $cardItems .= "<div style=\"text-align:center;padding:1.5rem;\">
                <div style=\"font-size:2rem;margin-bottom:0.75rem;\">{$icon}</div>
                <div style=\"font-size:0.8rem;font-weight:700;color:rgba(255,255,255,0.5);letter-spacing:0.08em;text-transform:uppercase;margin-bottom:0.4rem;\">{$label}</div>
                <div style=\"font-size:1rem;font-weight:600;color:#ffffff;\">{$value}</div>
            </div>";
        }

        return "<section id=\"tb-{$id}\" style=\"{$bgStyle};padding:4rem 0;\">
<div style=\"max-width:1200px;margin:0 auto;padding:0 1.5rem;\">
  <div style=\"text-align:center;margin-bottom:2.5rem;\">
    <h2 style=\"font-size:clamp(1.5rem,2.5vw,2.25rem);font-weight:800;color:#ffffff;margin:0 0 0.75rem;\">{$title}</h2>
    <p style=\"color:rgba(255,255,255,0.7);font-size:1rem;margin:0;\">{$subtitle}</p>
  </div>
  <div style=\"display:flex;flex-wrap:wrap;justify-content:center;gap:1rem;\">{$cardItems}</div>
</div>
</section>";
    }

    protected function renderContact_info(array $props, string $inner, string $id): string
    {
        return $this->renderContactInfo($props, $inner, $id);
    }

    protected function renderSiteFooter(array $props, string $inner, string $id): string
    {
        $logoText   = $this->escape($props['logo_text'] ?? 'Acool HVAC');
        $tagline    = $this->escape($props['tagline'] ?? 'Professional air conditioning & HVAC repair services you can trust.');
        $copyright  = $this->escape($props['copyright'] ?? '© ' . date('Y') . ' Acool HVAC. All rights reserved.');
        $bg         = $this->escapeAttr($props['background'] ?? '#0f172a');
        $links      = is_array($props['links'] ?? null) ? $props['links'] : [];
        $social     = is_array($props['social_links'] ?? null) ? $props['social_links'] : [];
        $columns    = is_array($props['columns'] ?? null) ? $props['columns'] : [];

        $bgStyle = str_starts_with($bg, '#') ? "background:{$bg}" : "background:#0f172a";

        $colsHtml = '';
        foreach ($columns as $col) {
            $colTitle = $this->escape(is_array($col) ? ($col['title'] ?? '') : '');
            $colLinks = is_array($col) && isset($col['links']) ? $col['links'] : [];
            $linkItems = '';
            foreach ($colLinks as $lnk) {
                $lhref  = $this->escapeAttr(is_array($lnk) ? ($lnk['url'] ?? '#') : '#');
                $llabel = $this->escape(is_array($lnk) ? ($lnk['label'] ?? '') : (string)$lnk);
                $linkItems .= "<li style=\"margin-bottom:0.5rem;\"><a href=\"{$lhref}\" style=\"color:rgba(255,255,255,0.6);text-decoration:none;font-size:0.875rem;transition:color 0.2s;\">{$llabel}</a></li>";
            }
            $colsHtml .= "<div>
                <h4 style=\"color:#ffffff;font-size:0.9rem;font-weight:700;margin:0 0 1rem;letter-spacing:0.04em;\">{$colTitle}</h4>
                <ul style=\"list-style:none;padding:0;margin:0;\">{$linkItems}</ul>
            </div>";
        }

        $socialHtml = '';
        foreach ($social as $s) {
            $surl   = $this->escapeAttr(is_array($s) ? ($s['url'] ?? '#') : '#');
            $slabel = $this->escape(is_array($s) ? ($s['label'] ?? '') : (string)$s);
            $icon   = $this->escape(is_array($s) ? ($s['icon'] ?? $slabel[0] ?? '●') : $slabel[0] ?? '●');
            $socialHtml .= "<a href=\"{$surl}\" target=\"_blank\" rel=\"noopener\" style=\"display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;background:rgba(255,255,255,0.1);border-radius:8px;color:rgba(255,255,255,0.7);text-decoration:none;font-size:0.875rem;\">{$icon}</a>";
        }

        $gridCols = count($columns) > 0 ? '1fr ' . implode(' ', array_fill(0, count($columns), '1fr')) : '1fr 1fr 1fr';

        return "<footer id=\"tb-{$id}\" style=\"{$bgStyle};padding:4rem 0 2rem;\">
<div style=\"max-width:1200px;margin:0 auto;padding:0 1.5rem;\">
  <div style=\"display:grid;grid-template-columns:{$gridCols};gap:3rem;margin-bottom:3rem;\">
    <div>
      <a href=\"/\" style=\"font-size:1.3rem;font-weight:800;color:#ffffff;text-decoration:none;display:block;margin-bottom:1rem;\">{$logoText}</a>
      <p style=\"color:rgba(255,255,255,0.55);font-size:0.875rem;line-height:1.7;margin:0 0 1.5rem;\">{$tagline}</p>
      <div style=\"display:flex;gap:0.75rem;flex-wrap:wrap;\">{$socialHtml}</div>
    </div>
    {$colsHtml}
  </div>
  <div style=\"border-top:1px solid rgba(255,255,255,0.1);padding-top:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;\">
    <p style=\"color:rgba(255,255,255,0.4);font-size:0.8rem;margin:0;\">{$copyright}</p>
    <div style=\"display:flex;gap:1.5rem;\">" .
      implode('', array_map(function($lnk) {
          $href  = $this->escapeAttr(is_array($lnk) ? ($lnk['url'] ?? '#') : '#');
          $label = $this->escape(is_array($lnk) ? ($lnk['label'] ?? '') : (string)$lnk);
          return "<a href=\"{$href}\" style=\"color:rgba(255,255,255,0.4);font-size:0.8rem;text-decoration:none;\">{$label}</a>";
      }, $links)) .
    "</div>
  </div>
</div>
</footer>";
    }

    protected function renderSite_footer(array $props, string $inner, string $id): string
    {
        return $this->renderSiteFooter($props, $inner, $id);
    }

    // ── End HVAC block renderers ──────────────────────────────────────────

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
    private function buildCssDeclarations(array $style): array
    {
        $css = [];
        $shadowMap = [
            'sm'   => '0 1px 3px rgba(0,0,0,0.12),0 1px 2px rgba(0,0,0,0.08)',
            'md'   => '0 4px 6px rgba(0,0,0,0.1),0 2px 4px rgba(0,0,0,0.08)',
            'lg'   => '0 10px 25px rgba(0,0,0,0.12),0 4px 10px rgba(0,0,0,0.08)',
            'xl'   => '0 20px 40px rgba(0,0,0,0.15)',
            'none' => 'none',
        ];
        if (!empty($style['bgColor'])) $css[] = "background-color:{$style['bgColor']}";
        if (!empty($style['textColor'])) $css[] = "color:{$style['textColor']}";
        if (!empty($style['bgGradientFrom']) && !empty($style['bgGradientTo'])) {
            $dir = $style['bgGradientDir'] ?? '135deg';
            $css[] = "background:linear-gradient({$dir},{$style['bgGradientFrom']},{$style['bgGradientTo']})";
        }
        if (!empty($style['fontSize'])) $css[] = "font-size:{$style['fontSize']}px";
        if (!empty($style['fontWeight'])) $css[] = "font-weight:{$style['fontWeight']}";
        if (!empty($style['lineHeight'])) $css[] = "line-height:{$style['lineHeight']}";
        if (!empty($style['textAlign'])) $css[] = "text-align:{$style['textAlign']}";
        if (!empty($style['letterSpacing'])) $css[] = "letter-spacing:{$style['letterSpacing']}px";
        if (isset($style['paddingTop'])    && $style['paddingTop']    !== '') $css[] = "padding-top:{$style['paddingTop']}px";
        if (isset($style['paddingRight'])  && $style['paddingRight']  !== '') $css[] = "padding-right:{$style['paddingRight']}px";
        if (isset($style['paddingBottom']) && $style['paddingBottom'] !== '') $css[] = "padding-bottom:{$style['paddingBottom']}px";
        if (isset($style['paddingLeft'])   && $style['paddingLeft']   !== '') $css[] = "padding-left:{$style['paddingLeft']}px";
        if (isset($style['marginTop'])    && $style['marginTop']    !== '') $css[] = "margin-top:{$style['marginTop']}px";
        if (isset($style['marginBottom']) && $style['marginBottom'] !== '') $css[] = "margin-bottom:{$style['marginBottom']}px";
        if (isset($style['borderWidth']) && $style['borderWidth'] !== '') {
            $bStyle = $style['borderStyle'] ?? 'solid';
            $bColor = $style['borderColor'] ?? '#e5e7eb';
            $css[] = "border:{$style['borderWidth']}px {$bStyle} {$bColor}";
        }
        if (!empty($style['borderRadius'])) $css[] = "border-radius:{$style['borderRadius']}px";
        if (!empty($style['shadow']) && isset($shadowMap[$style['shadow']])) {
            $css[] = "box-shadow:{$shadowMap[$style['shadow']]}";
        }
        if (isset($style['opacity']) && $style['opacity'] !== '' && (float)$style['opacity'] != 1.0) {
            $css[] = "opacity:{$style['opacity']}";
        }
        if (!empty($style['animationDuration'])) {
            $css[] = "--tb-anim-duration:{$style['animationDuration']}s";
        }
        if (!empty($style['hidden'])) $css[] = 'display:none';
        return $css;
    }

    // TB5: Apply desktop + responsive breakpoint styles
    protected function applyBlockStyle(string $html, array $style, array $responsive = [], string $id = ''): string
    {
        $desktopCss = $this->buildCssDeclarations($style);
        $cls = [];

        if (!empty($style['hideDesktop'])) $cls[] = 'tb-hide-desktop';
        if (!empty($style['hideTablet']))  $cls[] = 'tb-hide-tablet';
        if (!empty($style['hideMobile']))  $cls[] = 'tb-hide-mobile';
        if (!empty($style['animation']) && $style['animation'] !== 'none') {
            $cls[] = 'tb-anim-' . preg_replace('/[^a-z0-9_-]/i', '', $style['animation']);
        }
        if (!empty($style['customClass'])) {
            foreach (explode(' ', $style['customClass']) as $c) {
                $c = preg_replace('/[^a-zA-Z0-9_-]/', '', $c);
                if ($c) $cls[] = $c;
            }
        }

        // TB5: block-specific class for responsive targeting
        if ($id !== '' && !empty($responsive)) {
            $blkId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $id);
            $cls[] = "tb-blk-{$blkId}";
        }

        if (empty($desktopCss) && empty($cls)) return $html;

        $styleAttr = !empty($desktopCss) ? ' style="' . implode(';', $desktopCss) . '"' : '';
        $classAttr = !empty($cls) ? ' class="' . implode(' ', $cls) . '"' : '';
        $wrapped = "<div{$classAttr}{$styleAttr}>{$html}</div>";

        // TB5: inject responsive breakpoint <style> block
        if (!empty($responsive) && $id !== '') {
            $blkId    = preg_replace('/[^a-zA-Z0-9_\-]/', '', $id);
            $selector = ".tb-blk-{$blkId}";
            $mediaCss = '';

            $tabletCss = $this->buildCssDeclarations($responsive['tablet'] ?? []);
            if (!empty($tabletCss)) {
                $mediaCss .= "@media(max-width:1024px){{$selector}{" . implode(';', $tabletCss) . "}}";
            }
            $mobileCss = $this->buildCssDeclarations($responsive['mobile'] ?? []);
            if (!empty($mobileCss)) {
                $mediaCss .= "@media(max-width:480px){{$selector}{" . implode(';', $mobileCss) . "}}";
            }

            if ($mediaCss !== '') {
                $wrapped .= "<style data-tb-blk=\"{$blkId}\">{$mediaCss}</style>";
            }
        }

        return $wrapped;
    }

    protected function editableAttr(string $propKey, string $inlineType = 'text'): string
    {
        if (!($this->context['editor_mode'] ?? false)) return '';
        return " data-tb-editable=\"{$propKey}\" data-tb-inline-type=\"{$inlineType}\"";
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

