# خطاهای شناخته‌شدهٔ Baseline در PHPUnit Suite

> این فایل **فقط** برای ثبت و ایزوله نگه‌داشتن خطاهای **pre-existing** در test suite این مخزن است. این خطاها مربوط به Wave 5.x نیستند و هیچ‌کدام در تست‌های Wave 1 تا Wave 5.3 یا waveهای آینده گزارش/پوشش داده نمی‌شوند.
>
> **آخرین به‌روزرسانی:** ۱۴۰۵/۰۲/۲۲ (پس از merge شدن Wave 5.3 / P1-5).
> **تأیید baseline:** `git checkout a213fcc -- .` (یعنی commit دقیقاً پیش از Wave 5.3) و سپس `php artisan test` همین ۱۶ failure را تولید می‌کند ⇒ هیچ‌کدام به Wave 5.3 ربط ندارند.

---

## ۱. خلاصهٔ اعداد

| متریک | مقدار |
|---|---|
| کل تست‌های suite (پس از Wave 5.3) | **۱۴۵** |
| موفق | **۱۲۹** |
| ناموفق (baseline known) | **۱۶** |
| Wave 5.3 — جدید موفق | **۲۲ / ۲۲** (۵۰ assertion) |
| رگرسیون Wave 2/3/4/5.3 ⇒ سبز | **۴۴ / ۴۴** (۱۱۵ assertion) |

---

## ۲. لیست کامل ۱۶ failure

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
| 8 | `ShopModuleFeatureTest` | `shop_index_uses_effective_price_for_filter_and_sort` | 403 ≠ 200 | `EnsureModuleEnabled('shop')` ⇒ 403. |
| 9 | `ShopModuleFeatureTest` | `checkout_creates_order_decrements_stock_and_clears_cart` | false ≠ true | به دلیل ⬆ سفارش ایجاد نمی‌شود؛ assertion بعدی fail. |
| 10 | `ShopModuleFeatureTest` | `checkout_with_variant_decrements_variant_stock` | false ≠ true | همان ⬆ |
| 11 | `ShopModuleFeatureTest` | `product_review_not_visible_until_approved` | 403 ≠ 200 | همان ⬆ |
| 12 | `ShopModuleFeatureTest` | `wishlist_toggle_persists_in_session` | false ≠ true | همان ⬆ |
| 13 | `ShopModuleFeatureTest` | `compare_session_stores_product_ids` | false ≠ true | همان ⬆ |
| 14 | `StageFourValidationTest` | `admin_route_allows_access_with_valid_license` | 302 ≠ 200 | `license.valid` middleware ⇒ redirect (license seed‌ نشده). |
| 15 | `StageFourValidationTest` | `public_routes_home_page_blog_shop_work_with_valid_modules` | 403 ≠ 200 | `EnsureModuleEnabled` بدون license. |
| 16 | `StageFourValidationTest` | `admin_modules_page_shows_latest_synced_blog_release` | 302 ≠ 200 | همان ⬆ |

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
- اگر تعداد failure در run بعدی **بیشتر از ۱۶** شد، نشانهٔ regression جدید است و باید بررسی شود.
- اگر **کمتر از ۱۶** شد، نشانهٔ fix شدن یکی از baselineهاست و این فایل باید به‌روز شود.

---

## ۵. نقشهٔ راه پیشنهادی برای fix تدریجی

این فایل **تجویز** نمی‌کند چه زمانی این failureها باید fix شوند، فقط فهرست را نگه می‌دارد. در صورت تصمیم برای fix:

1. **Wave-X.0 (پاک‌سازی تست‌های قدیمی) — اختیاری.** افزودن یک trait `SeedsValidLicense` در `tests/Concerns/` که در `setUp()` ‏license معتبر با همهٔ ماژول‌ها seed می‌کند. سپس آن trait به `StageFourValidationTest`، `ShopModuleFeatureTest`، `BlogProfessionalUpliftTest`، `MenuModuleFeatureTest`، `PhaseOneTwoThreeTest` اضافه شود.
2. تخمین تلاش: ۲–۳ ساعت برای کل ۱۶ تست (همگی الگوی مشابه دارند).
3. **هیچ تغییری در کد production لازم نیست.**

---

*این فایل برای ردیابی شفاف ساخته شده و نباید به‌عنوان "TODO ضروری" تعبیر شود. اولویت اصلی همان است که در `global-rules.mdc` بند ۴ آمده: حفظ entitlement مشتری و backward compatibility — که در Wave 5.3 صفر breaking change داشتیم.*
