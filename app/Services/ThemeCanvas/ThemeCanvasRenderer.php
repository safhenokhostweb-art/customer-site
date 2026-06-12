<?php

namespace App\Services\ThemeCanvas;

use App\Helpers\CurrencyHelper;
use App\Helpers\ImageHelper;
use App\Helpers\Jalali;
use App\Helpers\SiteHelper;
use App\Models\Course;
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

    public function setThemeData(array $themeData): self
    {
        $this->themeData = $themeData;
        return $this;
    }

    /**
     * Override renderBlock to intercept module:* types before schema validation.
     * The schema registry doesn't know about module blocks, so we bypass it here.
     */
    public function renderBlock(array $block): string
    {
        $type = $block['type'] ?? 'text';
        $id   = $block['id'] ?? Str::random(8);

        if (str_starts_with($type, 'module:')) {
            try {
                $props = $block['props'] ?? $block['data'] ?? [];
                return $this->renderModuleBlock($type, $props, $id);
            } catch (\Throwable $e) {
                return $this->renderPlaceholder($id, $type, 'render_error');
            }
        }

        return parent::renderBlock($block);
    }

    // ── Module block dispatcher ───────────────────────────────────────────────

    protected function renderModuleBlock(string $type, array $props, string $id): string
    {
        return match (true) {
            in_array($type, ['module:blog_posts', 'module:blog_featured', 'module:blog_categories'], true)
                => $this->renderModuleBlogPosts($props, $id),

            in_array($type, ['module:shop_product_grid', 'module:shop_product_slider', 'module:products', 'module:product_grid'], true)
                => $this->renderModuleProductGrid($props, $id),

            $type === 'module:shop_categories'
                => $this->renderModuleShopCategories($props, $id),

            $type === 'module:shop_cart_button'
                => $this->renderModuleShopCartButton($props, $id),

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

            default => $this->renderModuleGenericPlaceholder($type, $props, $id),
        };
    }

    // ── Blog ─────────────────────────────────────────────────────────────────

    protected function renderModuleBlogPosts(array $props, string $id): string
    {
        if (!SiteHelper::blogEnabled()) {
            return '';
        }

        $title  = $this->escape($props['title'] ?? 'آخرین مطالب');
        $limit  = max(1, (int) ($props['limit'] ?? 6));
        $layout = $props['layout'] ?? 'grid';
        $posts  = Post::published()->latest('published_at')->take($limit)->get();

        if ($posts->isEmpty()) {
            return "<div id=\"tb-{$id}\" class=\"tb-module-blog tb-empty-state\">
                <p class=\"tb-text\">هنوز مطلبی منتشر نشده است.</p>
                <a href=\"" . e(route('blog.index')) . "\" class=\"tb-btn tb-btn-secondary\">مشاهده وبلاگ</a>
            </div>";
        }

        $colsClass = $layout === 'list' ? 'tb-grid-cols-1' : 'tb-grid-cols-3';
        $cards = [];
        foreach ($posts as $post) {
            $url     = route('blog.show', $post->slug);
            $t       = $this->escape($post->title);
            $excerpt = $this->escape(Str::limit(strip_tags($post->excerpt ?? $post->body ?? ''), 110));
            $date    = $post->published_at ? Jalali::formatFa($post->published_at, 'Y/m/d') : '';
            $imgHtml = !empty($post->featured_image)
                ? "<div class=\"tb-blog-card-img\"><img src=\"" . e(ImageHelper::url($post->featured_image)) . "\" alt=\"{$t}\" loading=\"lazy\"></div>"
                : "<div class=\"tb-blog-card-img tb-blog-card-noimg\">📰</div>";
            $cards[] = "<a href=\"{$url}\" class=\"tb-blog-card\">{$imgHtml}<div class=\"tb-blog-card-body\">
                <h3 class=\"tb-blog-card-title\">{$t}</h3>
                <p class=\"tb-blog-card-meta\">{$date}</p>
                <p class=\"tb-blog-card-excerpt\">{$excerpt}</p>
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

    // ── Shop ─────────────────────────────────────────────────────────────────

    protected function renderModuleProductGrid(array $props, string $id): string
    {
        if (!SiteHelper::shopEnabled()) {
            return '';
        }

        $title   = $this->escape($props['title'] ?? 'محصولات');
        $limit   = max(1, (int) ($props['limit'] ?? 8));
        $columns = max(2, min(4, (int) ($props['columns'] ?? 4)));
        $source  = $props['source'] ?? 'recent';

        $query = Product::query()->active();
        if ($source === 'featured') {
            $query->where('is_featured', true);
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

    protected function renderModuleShopCategories(array $props, string $id): string
    {
        if (!SiteHelper::shopEnabled()) {
            return '';
        }
        $title = $this->escape($props['title'] ?? 'دسته‌بندی‌ها');
        return "<div id=\"tb-{$id}\" class=\"tb-module-shop-categories\">
            <h2 class=\"tb-heading tb-section-title\">{$title}</h2>
            <div style=\"text-align:center;\"><a href=\"" . e(route('shop.index')) . "\" class=\"tb-btn tb-btn-secondary\">مشاهده فروشگاه</a></div>
        </div>";
    }

    protected function renderModuleShopCartButton(array $props, string $id): string
    {
        if (!SiteHelper::shopEnabled()) {
            return '';
        }
        $label   = $this->escape($props['label'] ?? 'سبد خرید');
        $cartUrl = route('shop.cart');
        $count   = 0;
        foreach (session('cart', []) as $item) {
            $count += (int) ($item['quantity'] ?? 0);
        }
        $badge = $count > 0
            ? "<span style=\"background:#ef4444;color:#fff;font-size:0.7rem;padding:0.1rem 0.4rem;border-radius:999px;\">{$count}</span>"
            : '';
        return "<a id=\"tb-{$id}\" href=\"{$cartUrl}\" class=\"tb-cart-link\" aria-label=\"{$label}\">🛒 {$label} {$badge}</a>";
    }

    // ── Courses ───────────────────────────────────────────────────────────────

    protected function renderModuleCoursesGrid(array $props, string $id): string
    {
        if (!SiteHelper::coursesEnabled()) {
            return '';
        }

        $title   = $this->escape($props['title'] ?? 'دوره‌های آموزشی');
        $limit   = max(1, (int) ($props['limit'] ?? 6));
        $columns = max(2, min(4, (int) ($props['columns'] ?? 3)));
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

        $colsClass = "tb-grid-cols-{$columns}";
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
            $cards[] = "<a href=\"{$url}\" class=\"tb-product-card\" style=\"text-align:right;\">
                {$imgHtml}
                <div class=\"tb-product-card-inner\">
                    <h3 class=\"tb-product-title\">{$t}</h3>
                    <p style=\"font-size:0.85rem;color:#6b7280;margin:0.25rem 0;\">{$excerpt}</p>
                    <div class=\"tb-product-price\">{$price}</div>
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
        $label = $this->escape($props['label'] ?? 'کاربر فعال');
        return "<div id=\"tb-{$id}\" class=\"tb-module-stats\">
            <div style=\"display:inline-flex;align-items:center;gap:0.5rem;background:#f3f4f6;border-radius:999px;padding:0.4rem 1rem;font-size:0.9rem;\">
                <span style=\"color:#4f46e5;font-size:1rem;\">👥</span>
                <span>{$label}</span>
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
        $items[] = "<p class=\"tb-text\"><a href=\"" . e(route('contact.show')) . "\" class=\"tb-btn tb-btn-primary\" style=\"margin-top:0.5rem;\">ارسال پیام</a></p>";

        return "<div id=\"tb-{$id}\" class=\"tb-module-contact\">" . implode('', $items) . "</div>";
    }

    // ── FAQ ───────────────────────────────────────────────────────────────────

    protected function renderModuleFaqDynamic(array $props, string $id): string
    {
        $title = $this->escape($props['title'] ?? 'سوالات متداول');
        $items = $props['items'] ?? [];

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
            $rows[] = "<details class=\"tb-faq-item\">
                <summary class=\"tb-faq-question\">{$q}</summary>
                <div class=\"tb-faq-answer\">{$a}</div>
            </details>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-faq\">" . implode('', $rows) . "</div>";
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

        $items = [];
        foreach ($links as $link) {
            $url   = $this->escapeAttr($link['url'] ?? '#');
            $label = $this->escape($link['label'] ?? '');
            $items[] = "<a href=\"{$url}\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"tb-social-link\" aria-label=\"{$label}\">{$label}</a>";
        }

        return "<div id=\"tb-{$id}\" class=\"tb-social-icons\">" . implode('', $items) . "</div>";
    }

    protected function renderModuleNotificationsBar(array $props, string $id): string
    {
        $style   = $props['style'] ?? 'top-bar';
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

        return "<div id=\"tb-{$id}\" class=\"tb-notifications-bar\" style=\"background:#4f46e5;color:#fff;padding:0.6rem 1rem;text-align:center;display:flex;justify-content:center;align-items:center;gap:0.75rem;\">
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

        return "<div id=\"tb-{$id}\" class=\"tb-module-referral\" style=\"background:#fef9c3;border:1px solid #eab308;border-radius:0.75rem;padding:1.5rem;text-align:center;\">
            <h3 style=\"font-weight:700;margin:0 0 0.5rem;\">🎁 {$title}</h3>
            <p style=\"color:#713f12;margin:0 0 1rem;\">{$subtitle}</p>
            <a href=\"" . e(route('account.referrals.index')) . "\" class=\"tb-btn tb-btn-primary\">مشاهده لینک معرفی</a>
        </div>";
    }

    // ── Generic placeholder ───────────────────────────────────────────────────

    protected function renderModuleGenericPlaceholder(string $type, array $props, string $id): string
    {
        // Silent in production — module may not be enabled
        return '';
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

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
