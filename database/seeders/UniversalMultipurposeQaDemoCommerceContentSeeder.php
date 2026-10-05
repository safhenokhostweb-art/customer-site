<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\License;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\MediaStorage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Commercial Productization fase-5 — real, realistic-looking Product/
 * Category/Post demo content for the LOCAL customer-site database, so
 * Product Detail and Article Detail (real, official routes —
 * ShopController::show / BlogController::show) have genuine data to bind
 * to instead of only ever showing their honest empty-state. Everything
 * here goes through real Eloquent models (Product::create(),
 * ProductCategory::create(), Post::create(), Category::create(),
 * License::create()) — the same architecture-respecting path the rest of
 * this codebase already uses, never a raw INSERT.
 *
 * Idempotent: every row is looked up by its real unique key (slug, or
 * domain for the License) and updated in place rather than duplicated —
 * re-running this seeder any number of times converges to the same state.
 *
 * This ONLY ever touches the LOCAL customer-site database — it never
 * calls out to saas-platform, and (like the sibling Theme seeder) is only
 * ever run explicitly, never automatically against a real Production
 * database.
 */
class UniversalMultipurposeQaDemoCommerceContentSeeder extends Seeder
{
    private array $images = [];

    public function run(): void
    {
        $this->seedLocalShopBlogLicense();
        $this->seedImages();

        $categories = $this->seedProductCategories();
        $this->seedProducts($categories);

        $author = $this->seedAuthor();
        $blogCategory = $this->seedBlogCategory();
        $this->seedPosts($author, $blogCategory);
    }

    /**
     * `shop`/`blog` are license-only gates (SiteHelper::moduleEnabled()) —
     * without a real, valid local License row whose domain matches this
     * request host, ShopController/BlogController abort(404) on every
     * route regardless of how much real Product/Post data exists. This is
     * the same real license-seeding shape tests/Concerns/
     * SeedsValidLicenseWithShop already establishes as the correct local
     * path — never a raw settings/config bypass.
     */
    private function seedLocalShopBlogLicense(): void
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'customer-site.test';

        License::updateOrCreate(
            ['domain' => $host],
            [
                'license_key' => 'LOCAL-DEV-' . strtoupper(substr(md5($host), 0, 12)),
                'expires_at' => now()->addYears(5),
                'modules' => ['shop', 'blog', 'forms'],
                'platform_valid' => true,
                'last_validated_at' => now(),
            ]
        );
    }

    private function seedAuthor(): User
    {
        return User::updateOrCreate(
            // Keep the editorial fixture distinct from BrowserHarnessSetupCommand's
            // deterministic admin account. Reusing 09120000001 here changed the
            // real browser admin password on every --universal setup run.
            ['mobile' => '09121111111'],
            [
                'name' => 'سارا محمدی',
                'password' => Hash::make(bin2hex(random_bytes(16))),
                'is_admin' => true,
                'bio' => 'سردبیر و نویسندهٔ محتوای پلتفرم',
            ]
        );
    }

    private function seedProductCategories(): array
    {
        $defs = [
            ['name' => 'پوشاک و مد', 'slug' => 'fashion', 'order' => 1],
            ['name' => 'دیجیتال و الکترونیک', 'slug' => 'digital', 'order' => 2],
            ['name' => 'خانه و آشپزخانه', 'slug' => 'home-kitchen', 'order' => 3],
            ['name' => 'زیبایی و سلامت', 'slug' => 'beauty-health', 'order' => 4],
            ['name' => 'ورزش و سفر', 'slug' => 'sports-travel', 'order' => 5],
            ['name' => 'کتاب و لوازم تحریر', 'slug' => 'books-stationery', 'order' => 6],
        ];
        $out = [];
        foreach ($defs as $d) {
            $out[$d['slug']] = ProductCategory::updateOrCreate(['slug' => $d['slug']], $d);
        }

        return $out;
    }

    private function seedBlogCategory(): Category
    {
        return Category::updateOrCreate(
            ['slug' => 'business-guide'],
            ['name' => 'راهنمای کسب‌وکار', 'description' => 'مقالات آموزشی برای رشد کسب‌وکار آنلاین شما', 'order' => 1]
        );
    }

    private function seedProducts(array $categories): void
    {
        $defs = [
            [
                'slug' => 'leather-crossbody-bag', 'title' => 'کیف چرم دست‌دوز مدل آرمانی',
                'category' => 'fashion', 'price' => 1850000, 'sale_price' => 1490000, 'stock' => 14, 'is_new' => true,
                'description' => 'کیف چرم طبیعی دست‌دوز با بند قابل‌تنظیم، مناسب استفادهٔ روزمره و مهمانی. دوخت دستی و چرم اصل ایرانی.',
                'gallery' => 2,
            ],
            [
                'slug' => 'smart-watch-x1', 'title' => 'ساعت هوشمند مدل ایکس‌وان',
                'category' => 'digital', 'price' => 3450000, 'sale_price' => null, 'stock' => 27, 'is_new' => true,
                'description' => 'ساعت هوشمند با صفحه‌نمایش AMOLED، ضدآب تا ۵۰ متر، سنجش ضربان قلب و باتری تا ۷ روز.',
                'gallery' => 3,
            ],
            [
                'slug' => 'wireless-earbuds-pro', 'title' => 'هندزفری بی‌سیم پرو با حذف نویز',
                'category' => 'digital', 'price' => 1290000, 'sale_price' => 990000, 'stock' => 40, 'is_new' => false,
                'description' => 'هندزفری بی‌سیم با فناوری حذف نویز فعال (ANC)، کیفیت صدای استودیویی و شارژ سریع.',
                'gallery' => 2,
            ],
            [
                'slug' => 'ceramic-cookware-set', 'title' => 'سرویس قابلمهٔ سرامیکی ۸ پارچه',
                'category' => 'home-kitchen', 'price' => 4200000, 'sale_price' => 3570000, 'stock' => 9, 'is_new' => false,
                'description' => 'سرویس آشپزی سرامیکی ضدچسب، مناسب اجاق گاز و القایی، بدون مواد شیمیایی مضر.',
                'gallery' => 2,
            ],
            [
                'slug' => 'linen-summer-dress', 'title' => 'پیراهن کتان تابستانه زنانه',
                'category' => 'fashion', 'price' => 890000, 'sale_price' => null, 'stock' => 0, 'is_new' => false,
                'description' => 'پیراهن کتان سبک و خنک، طراحی مینیمال، مناسب فصل گرما. تولید داخل با پارچهٔ باکیفیت.',
                'gallery' => 1,
            ],
            [
                'slug' => 'espresso-machine-home', 'title' => 'دستگاه اسپرسوساز خانگی نیمه‌حرفه‌ای',
                'category' => 'home-kitchen', 'price' => 6800000, 'sale_price' => 5990000, 'stock' => 5, 'is_new' => true,
                'description' => 'اسپرسوساز خانگی با پمپ ۱۵ بار، دیگ بخار برای کف شیر، مناسب علاقه‌مندان قهوهٔ حرفه‌ای.',
                'gallery' => 3,
            ],
        ];

        foreach ($defs as $d) {
            $product = Product::updateOrCreate(
                ['slug' => $d['slug']],
                [
                    'title' => $d['title'],
                    'description' => $d['description'],
                    'category_id' => $categories[$d['category']]->id,
                    'price' => $d['price'],
                    'sale_price' => $d['sale_price'],
                    'stock' => $d['stock'],
                    'status' => Product::STATUS_PUBLISHED,
                    'is_new' => $d['is_new'],
                    'sku' => 'UTQA-' . strtoupper(substr($d['slug'], 0, 6)),
                    'image' => $this->img($d['slug'] . '_main'),
                    'min_order_qty' => 1,
                    'order' => 0,
                ]
            );

            // Idempotent gallery: clear and re-attach this product's own
            // gallery rows only (never touches any other product's images).
            ProductImage::where('product_id', $product->id)->delete();
            for ($i = 1; $i <= $d['gallery']; $i++) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $this->img($d['slug'] . '_gallery_' . $i),
                    'sort_order' => $i,
                ]);
            }
        }
    }

    private function seedPosts(User $author, Category $category): void
    {
        $defs = [
            [
                'slug' => 'guide-online-store-2026',
                'title' => 'راهنمای کامل راه‌اندازی فروشگاه آنلاین در ۲۰۲۶',
                'excerpt' => 'از انتخاب محصول تا اولین سفارش — هر آنچه برای شروع یک فروشگاه آنلاین موفق نیاز دارید.',
                'days_ago' => 2,
            ],
            [
                'slug' => 'seo-basics-persian-sites',
                'title' => 'اصول سئوی فنی برای سایت‌های فارسی‌زبان',
                'excerpt' => 'چگونه ساختار سایت خود را برای موتورهای جست‌وجو و کاربران فارسی‌زبان بهینه کنیم.',
                'days_ago' => 6,
            ],
            [
                'slug' => 'choosing-payment-gateway-iran',
                'title' => 'راهنمای انتخاب درگاه پرداخت مناسب برای کسب‌وکار شما',
                'excerpt' => 'مقایسهٔ معیارهای مهم در انتخاب درگاه پرداخت: کارمزد، سرعت تسویه و پشتیبانی.',
                'days_ago' => 10,
            ],
            [
                'slug' => 'content-marketing-small-business',
                'title' => 'بازاریابی محتوایی برای کسب‌وکارهای کوچک',
                'excerpt' => 'با بودجهٔ محدود چگونه محتوایی بسازیم که مشتری واقعی جذب کند.',
                'days_ago' => 15,
            ],
            [
                'slug' => 'customer-support-best-practices',
                'title' => 'بهترین شیوه‌های پشتیبانی مشتری در فروشگاه‌های آنلاین',
                'excerpt' => 'پاسخ‌گویی سریع و درست، مهم‌ترین عامل بازگشت مشتری به فروشگاه شماست.',
                'days_ago' => 21,
            ],
            [
                'slug' => 'mobile-first-design-why-it-matters',
                'title' => 'چرا طراحی موبایل‌محور دیگر یک انتخاب نیست؟',
                'excerpt' => 'بیش از ۷۰٪ بازدیدهای فروشگاه‌های ایرانی از موبایل است — سایت شما آماده است؟',
                'days_ago' => 28,
            ],
        ];

        foreach ($defs as $i => $d) {
            $body = '<h2>مقدمه</h2>'
                . '<p>' . $d['excerpt'] . ' در این مقاله به‌صورت گام‌به‌گام بررسی می‌کنیم که چگونه می‌توانید این موضوع را در کسب‌وکار خود به‌درستی پیاده‌سازی کنید.</p>'
                . '<h2>چرا این موضوع اهمیت دارد؟</h2>'
                . '<p>بسیاری از کسب‌وکارهای کوچک و متوسط ایرانی، به‌دلیل نبود منبع فارسی قابل‌اعتماد، این مرحله را نادیده می‌گیرند یا اشتباه اجرا می‌کنند. نتیجه، از دست دادن فرصت‌های واقعی رشد است.</p>'
                . '<h2>گام‌های عملی</h2>'
                . '<p>در ادامه، مراحل عملی و قابل‌اجرا را مرور می‌کنیم — بدون نیاز به دانش فنی پیشرفته.</p>'
                . '<h3>گام اول</h3><p>ارزیابی وضعیت فعلی کسب‌وکار و شناسایی نقاط قابل‌بهبود.</p>'
                . '<h3>گام دوم</h3><p>اجرای تغییرات به‌صورت تدریجی و اندازه‌گیری نتیجه.</p>'
                . '<h2>جمع‌بندی</h2>'
                . '<p>با رعایت نکات این مقاله، می‌توانید نتیجهٔ ملموسی در کسب‌وکار آنلاین خود مشاهده کنید.</p>';

            Post::updateOrCreate(
                ['slug' => $d['slug']],
                [
                    'title' => $d['title'],
                    'excerpt' => $d['excerpt'],
                    'body' => $body,
                    'featured_image' => $this->img('post_' . $i),
                    'gallery_images' => [$this->img('post_' . $i . '_gallery')],
                    'category_id' => $category->id,
                    'user_id' => $author->id,
                    'is_published' => true,
                    'editorial_status' => Post::EDITORIAL_PUBLISHED,
                    'published_at' => now()->subDays($d['days_ago']),
                ]
            );
        }
    }

    private function img(string $name): string
    {
        return $this->images[$name] ?? '';
    }

    /** Store deterministic crops from committed commercial source photos. */
    private function seedImages(): void
    {
        $disk = app(MediaStorage::class)->diskNameFor('public');
        $dir = 'demo-commerce';

        $names = [
            'leather-crossbody-bag_main', 'leather-crossbody-bag_gallery_1', 'leather-crossbody-bag_gallery_2',
            'smart-watch-x1_main', 'smart-watch-x1_gallery_1', 'smart-watch-x1_gallery_2', 'smart-watch-x1_gallery_3',
            'wireless-earbuds-pro_main', 'wireless-earbuds-pro_gallery_1', 'wireless-earbuds-pro_gallery_2',
            'ceramic-cookware-set_main', 'ceramic-cookware-set_gallery_1', 'ceramic-cookware-set_gallery_2',
            'linen-summer-dress_main', 'linen-summer-dress_gallery_1',
            'espresso-machine-home_main', 'espresso-machine-home_gallery_1', 'espresso-machine-home_gallery_2', 'espresso-machine-home_gallery_3',
        ];
        foreach ($names as $n) {
            $this->images[$n] = $this->storeBrandedImage($disk, $dir, $n, 1000, 1000);
        }
        for ($i = 0; $i < 6; $i++) {
            $this->images['post_' . $i] = $this->storeBrandedImage($disk, $dir, 'post_' . $i, 1200, 675);
            $this->images['post_' . $i . '_gallery'] = $this->storeBrandedImage($disk, $dir, 'post_' . $i . '_gallery', 900, 600);
        }
    }

    private function storeBrandedImage(string $disk, string $dir, string $name, int $width, int $height): string
    {
        $path = "{$dir}/{$name}.jpg";
        $productOrder = [
            'leather-crossbody-bag' => 0,
            'smart-watch-x1' => 1,
            'wireless-earbuds-pro' => 2,
            'ceramic-cookware-set' => 3,
            'linen-summer-dress' => 4,
            'espresso-machine-home' => 5,
        ];
        $assetDir = database_path('seeders/assets/universal-multipurpose-qa-commerce');

        $productIndex = null;
        foreach ($productOrder as $slug => $index) {
            if (str_starts_with($name, $slug . '_')) {
                $productIndex = $index;
                break;
            }
        }

        if ($productIndex !== null) {
            $source = @imagecreatefromjpeg($assetDir . '/product-contact-sheet.jpg');
            if ($source === false) {
                throw new \RuntimeException('Missing commercial product contact sheet.');
            }
            $cellWidth = intdiv(imagesx($source), 3);
            $cellHeight = intdiv(imagesy($source), 2);
            $sourceX = ($productIndex % 3) * $cellWidth;
            $sourceY = intdiv($productIndex, 3) * $cellHeight;
            $sourceWidth = $cellWidth;
            $sourceHeight = $cellHeight;
        } else {
            $postIndex = (int) filter_var($name, FILTER_SANITIZE_NUMBER_INT);
            $postAssets = ['hero-business.jpg', 'business-team.jpg', 'mobile-commerce.jpg', 'technology-products.jpg', 'mountain-trust.jpg', 'business-team.jpg'];
            $source = @imagecreatefromjpeg($assetDir . '/' . $postAssets[$postIndex % count($postAssets)]);
            if ($source === false) {
                throw new \RuntimeException("Missing commercial demo source for [{$name}].");
            }
            $sourceWidth = imagesx($source);
            $sourceHeight = imagesy($source);
            $targetRatio = $width / $height;
            if (($sourceWidth / $sourceHeight) > $targetRatio) {
                $cropWidth = (int) round($sourceHeight * $targetRatio);
                $sourceX = intdiv($sourceWidth - $cropWidth, 2);
                $sourceWidth = $cropWidth;
                $sourceY = 0;
            } else {
                $cropHeight = (int) round($sourceWidth / $targetRatio);
                $sourceY = intdiv($sourceHeight - $cropHeight, 2);
                $sourceHeight = $cropHeight;
                $sourceX = 0;
            }
        }

        $image = imagecreatetruecolor($width, $height);
        imagecopyresampled($image, $source, 0, 0, $sourceX, $sourceY, $width, $height, $sourceWidth, $sourceHeight);
        imagedestroy($source);

        ob_start();
        imagejpeg($image, null, 87);
        $bytes = ob_get_clean();
        imagedestroy($image);

        Storage::disk($disk)->put($path, $bytes);

        return $path;
    }
}
