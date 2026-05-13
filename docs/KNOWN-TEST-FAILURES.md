# خطاهای شناخته‌شدهٔ Baseline در PHPUnit Suite

> این فایل **فقط** برای ثبت و ایزوله نگه‌داشتن خطاهای **pre-existing** در test suite این مخزن است. این خطاها مربوط به Wave 5.x نیستند و هیچ‌کدام در تست‌های Wave 1 تا Wave 6.2 یا waveهای آینده گزارش/پوشش داده نمی‌شوند.
>
> **آخرین به‌روزرسانی:** ۱۴۰۵/۰۲/۲۳ (پس از Wave 6.2 / P0-S3 — Shop test-suite stabilization).
> **تأیید baseline:** `git checkout a213fcc -- .` (یعنی commit دقیقاً پیش از Wave 5.3) و سپس `php artisan test` همان ۱۶ failure تاریخی را تولید می‌کند. در `HEAD` فعلی (پس از Wave 6.2) ۶ مورد آن (همگی متعلق به `ShopModuleFeatureTest`) با اضافه شدن `Tests\Concerns\SeedsValidLicenseWithShop` به‌صورت **bootstrap-only fix** (بدون تغییر منطق production) پاک شده‌اند ⇒ baseline فعلی **۱۰** failure. ۱۰ failure باقی‌مانده همگی روی `BlogProfessionalUpliftTest` / `MenuModuleFeatureTest` / `PhaseOneTwoThreeTest` / `StageFourValidationTest`اند و هنوز seed لایسنس ندارند.

---

## ۱. خلاصهٔ اعداد

| متریک | مقدار |
|---|---|
| کل تست‌های suite (پس از Wave 6.2) | **۱۶۳** |
| موفق | **۱۵۲** |
| ناموفق (baseline known، non-Shop) | **۱۰** |
| Risky (output-buffer hygiene در helperهای frontend، نه functional failure) | **۱** |
| Wave 6.1 — جدید موفق | **۲۲ / ۲۲** (۴۹ assertion) |
| Wave 6.2 — Shop suite recovered | **۶ / ۶** سبز (۵ pass + ۱ risky-pass) |
| رگرسیون Wave 2/3/4/5.x/6.x ⇒ سبز | **بدون regression** |

---

## ۲. لیست کامل ۱۰ failure

تمام failureها پیش از Wave 5.3 موجود بودند و **مستقیماً** ناشی از موارد bootstrap هستند که تست‌های قدیمی به‌جای ایجاد license/module/migration معتبر در `setUp()`، مفروضات قدیمی پلتفرم را داشتند:

| # | فایل تست | متد | نوع خطای ظاهری | علت ریشه‌ای محتمل |
|---|---|---|---|---|
| 1 | `BlogProfessionalUpliftTest` | `future_scheduled_posts_are_hidden_from_public_blog_listing` | 404 ≠ 200 | ماژول `blog` در license تست فعال نیست ⇒ public blog 404 می‌دهد. |
| 2 | `BlogProfessionalUpliftTest` | `public_blog_hides_posts_not_editorially_published` | 404 ≠ 200 | همان ⬆ |
| 3 | `MenuModuleFeatureTest` | `admin_modules_page_shows_menu_professional_status_card` | 302 ≠ 200 | `license.valid` middleware ⇒ redirect به `admin.license.index` (license تست seed نمی‌شود). |
| 4 | `PhaseOneTwoThreeTest` | `shop_index_with_category_filter` | 404 ≠ 200 | `shop` در license تست فعال نیست. |
| 5 | `PhaseOneTwoThreeTest` | `shop_search` | 404 ≠ 200 | همان ⬆ |
| 6 | `PhaseOneTwoThreeTest` | `blog_search` | 404 ≠ 200 | `blog` در license تست فعال نیست. |
| 7 | `PhaseOneTwoThreeTest` | `blog_show_displays_related_posts_and_reading_time` | 404 ≠ 200 | همان ⬆ |
| 8 | `StageFourValidationTest` | `admin_route_allows_access_with_valid_license` | 302 ≠ 200 | `license.valid` middleware ⇒ redirect (license seed‌ نشده). |
| 9 | `StageFourValidationTest` | `public_routes_home_page_blog_shop_work_with_valid_modules` | 403 ≠ 200 | `EnsureModuleEnabled` بدون license. |
| 10 | `StageFourValidationTest` | `admin_modules_page_shows_latest_synced_blog_release` | 302 ≠ 200 | همان ⬆ |

### ۲.۱ Failureهای حذف‌شده در Wave 6.2 (Shop — ۶ مورد)

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
- پس از Wave 6.2 baseline ثابت **۱۰** failure است (همگی non-Shop).
- اگر تعداد failure در run بعدی **بیشتر از ۱۰** شد، نشانهٔ regression جدید است و باید بررسی شود.
- اگر **کمتر از ۱۰** شد، نشانهٔ fix شدن یکی از baselineهای باقی‌ماندهٔ blog/menu/stage4 است و این فایل باید به‌روز شود.
- باقی‌ماندهٔ ۱۰ مورد عمیقاً همان الگوی Wave 6.2 را دارد (همان trait `SeedsValidLicenseWithShop` با ماژول‌های `['blog','shop']`) و در waveای جداگانه با fix یک‌مرتبه قابل‌برداشت است؛ تا آن زمان bug production نیستند.

---

## ۵. نقشهٔ راه پیشنهادی برای fix تدریجی

این فایل **تجویز** نمی‌کند چه زمانی این failureها باید fix شوند، فقط فهرست را نگه می‌دارد. در صورت تصمیم برای fix:

1. **Wave 6.2 ⇒ done.** trait `Tests\Concerns\SeedsValidLicenseWithShop` در این مخزن ساخته شد (host-aware، `License::firstOrCreate(domain=APP_URL host)`). به `ShopModuleFeatureTest::setUp()` متصل شد ⇒ ۶ failure مرتبط با Shop سبز.
2. **Wave-X (پاک‌سازی باقی‌ماندهٔ تست‌های قدیمی) — اختیاری.** افزودن همان trait به `StageFourValidationTest`، `BlogProfessionalUpliftTest`، `MenuModuleFeatureTest`، `PhaseOneTwoThreeTest` (و در صورت نیاز ماژول `blog` به آرگومان seed). تخمین تلاش: ۱–۲ ساعت.
3. **هیچ تغییری در کد production لازم نیست** — تنها استثنای موجه: همان مایگریشن `2026_05_13_110000_add_timestamps_to_shop_pivots` که در Wave 6.2 ثبت شد و در واقع یک bug latent production (relations با `withTimestamps()` روی pivotهای فاقد ستون timestamp) را اصلاح کرد و در پروداکشن نیز side-effect مفید دارد.

---

*این فایل برای ردیابی شفاف ساخته شده و نباید به‌عنوان "TODO ضروری" تعبیر شود. اولویت اصلی همان است که در `global-rules.mdc` بند ۴ آمده: حفظ entitlement مشتری و backward compatibility — که در Wave 5.3 و Wave 6.x صفر breaking change داشتیم.*
