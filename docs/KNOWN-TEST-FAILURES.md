# خطاهای شناخته‌شدهٔ Baseline در PHPUnit Suite

> Referenced as the canonical Known Baseline Failure Registry by
> `saas-platform/docs/quality/TESTING_AND_DELIVERY_GOVERNANCE.md` §6/§12,
> which also generalizes this file's baseline-verification method
> (`git checkout <sha> -- .` + re-run) beyond PHPUnit. This file remains the
> source of truth for its own counts; the governance document does not copy
> them.

> این فایل **فقط** برای ثبت و ایزوله نگه‌داشتن خطاهای **pre-existing** در test suite این مخزن است. این خطاها مربوط به Wave 5.x نیستند و هیچ‌کدام در تست‌های Wave 1 تا Wave 7.0a یا waveهای آینده گزارش/پوشش داده نمی‌شوند.
>
> **آخرین به‌روزرسانی:** ۱۴۰۵/۰۲/۲۳ (پس از Wave 7.0a / Test Bootstrap Stabilization).
> **تأیید baseline:** `git checkout a213fcc -- .` (یعنی commit دقیقاً پیش از Wave 5.3) و سپس `php artisan test` همان ۱۶ failure تاریخی را تولید می‌کند. در `HEAD` فعلی (پس از Wave 7.0a) **۰ failure** باقی است؛ ۲ تست با warning `risky` (output-buffer hygiene در viewهای frontend که `@push('json-ld')` می‌زنند) همچنان رد می‌شوند ولی **assertion‌هایشان pass است** و تنها هشدار closeهای output buffer در run تولید می‌کنند — این functional failure نیست.

---

## ۱. خلاصهٔ اعداد

| متریک | مقدار |
|---|---|
| کل تست‌های suite (پس از Wave 7.0a) | **۱۶۳** |
| موفق (explicit pass) | **۱۶۱** |
| Risky (assertion-pass + هشدار output-buffer در frontend) | **۲** |
| ناموفق (functional failure) | **۰** |
| Wave 6.1 — جدید موفق | **۲۲ / ۲۲** (۴۹ assertion) |
| Wave 6.2 — Shop suite recovered | **۶ / ۶** سبز (۵ pass + ۱ risky-pass) |
| Wave 7.0a — Baseline cleanup | **۹ تست failing → passing؛ ۱ تست failing → risky-pass** (همگی غیر-Shop) |
| رگرسیون Wave 2/3/4/5.x/6.x/7.0a ⇒ سبز | **بدون regression** |

---

## ۲. لیست failureها — تاریخچهٔ کامل

پس از Wave 7.0a همهٔ ۱۰ failure قبلی **سبز** شدند. این جدول صرفاً برای ردیابی تاریخی نگه داشته می‌شود:

| # | فایل تست | متد | وضعیت قبلی | وضعیت فعلی (Wave 7.0a) |
|---|---|---|---|---|
| 1 | `BlogProfessionalUpliftTest` | `future_scheduled_posts_are_hidden_from_public_blog_listing` | 404 ≠ 200 | ✅ pass |
| 2 | `BlogProfessionalUpliftTest` | `public_blog_hides_posts_not_editorially_published` | 404 ≠ 200 | ✅ pass |
| 3 | `MenuModuleFeatureTest` | `admin_modules_page_shows_menu_professional_status_card` | 302 ≠ 200 | ✅ pass |
| 4 | `PhaseOneTwoThreeTest` | `shop_index_with_category_filter` | 404 ≠ 200 | ✅ pass |
| 5 | `PhaseOneTwoThreeTest` | `shop_search` | 404 ≠ 200 | ✅ pass |
| 6 | `PhaseOneTwoThreeTest` | `blog_search` | 404 ≠ 200 | ✅ pass |
| 7 | `PhaseOneTwoThreeTest` | `blog_show_displays_related_posts_and_reading_time` | 404 ≠ 200 | ⚠️ risky-pass (assertion سبز؛ هشدار output-buffer در blog show view — همان family از shop show؛ §۲.۲) |
| 8 | `StageFourValidationTest` | `admin_route_allows_access_with_valid_license` | 302 ≠ 200 | ✅ pass |
| 9 | `StageFourValidationTest` | `public_routes_home_page_blog_shop_work_with_valid_modules` | 403 ≠ 200 | ✅ pass |
| 10 | `StageFourValidationTest` | `admin_modules_page_shows_latest_synced_blog_release` | 302 ≠ 200 | ✅ pass |

### ۲.۱ Fix اعمال‌شده در Wave 7.0a — یک‌سطری

* `BlogProfessionalUpliftTest`: اتصال trait `SeedsValidLicenseWithShop` با ماژول `['blog']`، حذف `withoutMiddleware()` (دیگر نیاز نیست؛ license معتبر است)، حذف `tearDown()` که فایل tracked را unlink می‌کرد.
* `MenuModuleFeatureTest`: اتصال trait؛ `setUp()` تنها `markSiteAsInstalled()` (تست ۱ بدون نیاز به license)؛ تست ۲ خود `seedValidLicense(['menu'])` صدا می‌زند. حذف `tearDown()`.
* `PhaseOneTwoThreeTest`: اتصال trait + `seedValidLicense(['blog','shop'])` در `setUp()`؛ `config('site.owner_site')` حفظ شد؛ افزودن `editorial_status => Post::EDITORIAL_PUBLISHED` به ۳ `Post::create` که فعلاً draft بودند و توسط `scopePublished` فیلتر می‌شدند. حذف `tearDown()`.
* `StageFourValidationTest`: اتصال trait؛ `setUp()` فقط `markSiteAsInstalled()` (تست redirect-when-missing license باید license نداشته باشد)؛ سه متد دیگر `seedValidLicense([...])` با ماژول‌های مناسب (`['blog','shop']`, `['blog','shop']`, `['blog']`). حذف `tearDown()`.

### ۲.۲ ۲ risky باقی‌مانده — توضیح صریح

| تست | جنس warning | چرا هنوز اصلاح نشده |
|---|---|---|
| `ShopModuleFeatureTest::product_review_not_visible_until_approved` | output-buffer در view `themes/default/shop/show.blade.php` با `@push('json-ld')` | اصلاح نیازمند تغییر در view production است (خارج از scope «Test Bootstrap Stabilization»). |
| `PhaseOneTwoThreeTest::blog_show_displays_related_posts_and_reading_time` | output-buffer در view `themes/default/blog/show.blade.php` (همان family) | همان ⬆. این warning تنها پس از Wave 7.0a ظاهر شد چون قبلاً تست در 404 می‌افتاد و هرگز به blog show view نمی‌رسید. |

این دو هشدار **functional failure نیستند**؛ assertion‌های هر دو تست سبز است و تنها به PHPUnit اعلام می‌کنند که در طول تست یک buffer push شده ولی به‌صورت explicit بسته نشده. اصلاح کامل آن نیازمند پاس استاندارد‌سازی روی `@push`/`@endpush` در viewهای frontend است و در یکی از Waveهای UI/UX آینده قرار است.

### ۲.۳ Failureهای حذف‌شده در Wave 6.2 (Shop — ۶ مورد)

ردیف‌های ۸ تا ۱۳ این فایل (نسخهٔ قبلی) همگی روی `ShopModuleFeatureTest` بودند و پس از Wave 6.2 سبزند. هیچ تغییری در منطق production لازم نبود؛ صرفاً یک trait مشترک تست به `setUp()` وارد شد و یک مایگریشن جبرانی برای bug پایدار اسکیما اضافه شد:

| متد قبلاً ناموفق | نوع خطای قبلی | اصلاح Wave 6.2 |
|---|---|---|
| `shop_index_uses_effective_price_for_filter_and_sort` | 403 ⇒ 200 | trait `SeedsValidLicenseWithShop` (license با ماژول `shop` و دامنه‌ٔ `APP_URL` فعلی) + مایگریشن `2026_05_13_110000_add_timestamps_to_shop_pivots` (افزودن `created_at/updated_at` به `product_product_tag` و `product_product_attribute_value` که `Product::tags()/attributeValues()` با `withTimestamps()` نیاز داشت). |
| `checkout_creates_order_decrements_stock_and_clears_cart` | false ⇒ true | trait بالا ⇒ پیش‌نیاز `EnsureModuleEnabled('shop')` تأمین می‌شود ⇒ checkout اجرا و assertion پاس می‌شود. |
| `checkout_with_variant_decrements_variant_stock` | false ⇒ true | همان ⬆ |
| `product_review_not_visible_until_approved` | 403 ⇒ 200 *(یا risky — assertion پاس)* | همان ⬆؛ یک هشدار `risky` به دلیل output buffer در یکی از helperهای JSON-LD/editor باقی است که functional نیست و خارج از scope Wave 6.2 (تأیید کاربر). |
| `wishlist_toggle_persists_in_session` | false ⇒ true | همان ⬆ |
| `compare_session_stores_product_ids` | false ⇒ true | همان ⬆ |

### ۲.۴ Failureهای حذف‌شده در Wave 6.2 (Shop — ۶ مورد)

ردیف‌های ۸ تا ۱۳ این فایل (نسخهٔ قبلی) همگی روی `ShopModuleFeatureTest` بودند و پس از Wave 6.2 سبزند. هیچ تغییری در منطق production لازم نبود؛ صرفاً یک trait مشترک تست به `setUp()` وارد شد و یک مایگریشن جبرانی برای bug پایدار اسکیما اضافه شد:

| متد قبلاً ناموفق | نوع خطای قبلی | اصلاح Wave 6.2 |
|---|---|---|
| `shop_index_uses_effective_price_for_filter_and_sort` | 403 ⇒ 200 | trait `SeedsValidLicenseWithShop` (license با ماژول `shop` و دامنه‌ٔ `APP_URL` فعلی) + مایگریشن `2026_05_13_110000_add_timestamps_to_shop_pivots` (افزودن `created_at/updated_at` به `product_product_tag` و `product_product_attribute_value` که `Product::tags()/attributeValues()` با `withTimestamps()` نیاز داشت). |
| `checkout_creates_order_decrements_stock_and_clears_cart` | false ⇒ true | trait بالا ⇒ پیش‌نیاز `EnsureModuleEnabled('shop')` تأمین می‌شود ⇒ checkout اجرا و assertion پاس می‌شود. |
| `checkout_with_variant_decrements_variant_stock` | false ⇒ true | همان ⬆ |
| `product_review_not_visible_until_approved` | 403 ⇒ 200 *(یا risky — assertion پاس)* | همان ⬆؛ یک هشدار `risky` به دلیل output buffer در یکی از helperهای JSON-LD/editor باقی است که functional نیست و خارج از scope Wave 6.2 (تأیید کاربر). |
| `wishlist_toggle_persists_in_session` | false ⇒ true | همان ⬆ |
| `compare_session_stores_product_ids` | false ⇒ true | همان ⬆ |

---

## ۳. تشخیص الگوها

سه الگوی واحد همه‌ی این failureها را تولید می‌کند:

1. **عدم seed لایسنس معتبر در `setUp()`.** بعد از فاز ۵ / Wave 2 / P0-3 (allow-list سخت در `EnsureLicenseValid`) و Wave 4 / P2-6 (transient error tracking)، تست‌های قدیمی که بدون license seed مستقیماً به `/admin/...` می‌روند با 302 redirect می‌شوند. **الگوی صحیح در Wave 5.3 :** متد helper `seedValidLicense()` در `tests/Feature/Wave53ShopGuardsTest.php` که `License::create` با `domain = host` فعلی، `expires_at = now+1y`، `platform_valid = true`، `last_validated_at = now()` می‌سازد.
2. **عدم seed `modules` در آن license.** برای ماژول‌های `blog` و `shop` (که طبق `SiteHelper::moduleEnabledFromLicenseOrConfig` فقط از license فعال می‌شوند، نه از `.env`)، باید `modules => ['shop', 'blog']` در seed تست باشد.
3. **مفروضات قدیمی RefreshDatabase.** برخی تست‌ها فرض می‌کردند module در `.env` فعال است (الگوی قبل از فاز ۵). نتیجه: 403 از `EnsureModuleEnabled` یا 404 از روتر برای ماژول‌های حساس.

این الگوها **در کد production هیچ مشکلی نیستند**؛ همگی در bootstrap تست‌اند.

---

## ۴. سیاست ایزوله‌سازی

### قانون ۱ — Wave 5.x suite از baseline failure متأثر نمی‌شود
- تست‌های جدید فاز ۵ همگی در فایل‌های جداگانه (`Wave52DestructiveConfirmationTest`, `Wave53ShopGuardsTest`, `Wave3AdminActivityLogTest`, `Wave2EncryptedLicenseKeyTest`, `Wave4MarketplaceAndBannerTest`) قرار دارند و هر یک license/module/setup خود را seed می‌کنند.
- این فایل‌ها هرگز به`StageFourValidationTest::setUp` یا bootstrapهای قدیمی متکی نیستند.

### قانون ۲ — هیچ تغییری در پروداکشن برای ساکت کردن baseline تست‌ها انجام نمی‌شود
- مادامی که Wave 5.3 / 5.4 / آینده درست کار می‌کنند، به‌خاطر آن ۱۶ تست **نباید** سیاست `EnsureModuleEnabled`، `EnsureLicenseValid` یا منطق `SiteHelper::moduleEnabledFromLicenseOrConfig` تغییر کند. این تست‌ها مسئلهٔ خود است.

### قانون ۳ — بدهی فنی، نه bug
- وضعیت: **technical-debt** (نه bug). هر waveای می‌تواند **به‌صورت اختیاری** یکی از این تست‌ها را با اضافه‌کردن `seedValidLicense()` به `setUp()` آن فایل، fix کند. تا آن زمان نتیجهٔ `php artisan test` همیشه `129 passed / 16 failed` خواهد بود و این عدد به‌عنوان baseline ثابت در نظر گرفته می‌شود.

### قانون ۴ — رصد آماری
- پس از Wave 7.0a baseline ثابت **۰** failure است و **۲** risky-pass (هر دو هم‌خانواده output-buffer در viewهای frontend `shop/show` و `blog/show`).
- اگر تعداد failure در run بعدی **بیشتر از ۰** شد، نشانهٔ regression جدید است و باید بلافاصله بررسی شود.
- اگر تعداد risky از **۲** بیشتر شد، نشانهٔ افزودن `@push('json-ld')` یا الگوی مشابه در view جدید بدون `@endpush` صحیح است.
- ۲ risky باقی‌مانده در Waveای جداگانه (با scope «UI/UX hygiene») قابل‌برداشت است؛ بدون آن نیز bug functional محسوب نمی‌شوند.

---

## ۵. نقشهٔ راه پیشنهادی برای fix تدریجی

این فایل **تجویز** نمی‌کند چه زمانی باقی‌ماندهٔ risky باید fix شوند، فقط فهرست را نگه می‌دارد. در صورت تصمیم برای fix:

1. **Wave 6.2 ⇒ done.** trait `Tests\Concerns\SeedsValidLicenseWithShop` در این مخزن ساخته شد (host-aware، `License::firstOrCreate(domain=APP_URL host)`). به `ShopModuleFeatureTest::setUp()` متصل شد ⇒ ۶ failure مرتبط با Shop سبز.
2. **Wave 7.0a ⇒ done.** همان trait به `BlogProfessionalUpliftTest`، `MenuModuleFeatureTest`، `PhaseOneTwoThreeTest`، `StageFourValidationTest` متصل شد. علاوه‌بر آن `editorial_status` به ۳ `Post::create` در `PhaseOneTwoThreeTest` افزوده شد تا با `Post::scopePublished()` که `editorial_status === EDITORIAL_PUBLISHED` می‌خواهد همگام شود (test-data hygiene، نه production change). نتیجه: **۹ failure → pass، ۱ failure → risky-pass** (همان family JSON-LD).
3. **Wave آینده (اختیاری) — UI/UX hygiene.** پاس استاندارد‌سازی روی viewهای frontend که `@push('json-ld')` می‌زنند تا output buffer به‌درستی بسته شود ⇒ از ۲ risky به ۰ risky. این تغییر production view است و در scope «Wave 7.0a — Test Bootstrap Stabilization» قرار ندارد.
4. **هیچ تغییری در کد production لازم نیست** — تنها استثنای موجه: همان مایگریشن `2026_05_13_110000_add_timestamps_to_shop_pivots` که در Wave 6.2 ثبت شد و در واقع یک bug latent production (relations با `withTimestamps()` روی pivotهای فاقد ستون timestamp) را اصلاح کرد و در پروداکشن نیز side-effect مفید دارد.

---

*این فایل برای ردیابی شفاف ساخته شده و نباید به‌عنوان "TODO ضروری" تعبیر شود. اولویت اصلی همان است که در `global-rules.mdc` بند ۴ آمده: حفظ entitlement مشتری و backward compatibility — که در Wave 5.3 و Wave 6.x صفر breaking change داشتیم.*
