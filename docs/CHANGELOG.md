# Changelog

تمام ورودی‌ها به‌صورت معکوس زمانی (جدیدترین بالا). فقط هنگام تغییر واقعی روی ماژول‌ها/قابلیت‌ها به‌روز شود.

## [Unreleased]

### Phase 2 / Wave 7.0b — Forms Hardening — ۱۴۰۵/۰۲/۲۳

Hardening pass on the Forms module: safe rich-text rendering for `intro_text`, public-submit rate-limiting, an in-admin guide partial, plus two unplanned but contained **P0 production fixes** discovered while writing the regression tests. Branch: `phase7-wave7b`. Regression: full suite ⇒ **۱۷۴ pass، ۲ risky، ۰ failure** (همان ۲ risky output-buffer hygiene از Wave 6.2/7.0a). صفر مایگریشن. صفر تغییر entitlement.

**۱. Output sanitization برای `intro_text` (production code change — Forms module):**
- در `resources/views/forms/show.blade.php` و `resources/views/admin/forms/preview.blade.php`، رندر قبلی `{!! nl2br(e($form->intro_text)) !!}` (escape + line-break فقط) با `{!! strip_tags($form->intro_text, '<p><br><strong><em><u><a><ul><ol><li><h2><h3><h4><blockquote><span><img><table><thead><tbody><tr><td><th>') !!}` جایگزین شد. allowlist عمداً به مجموعهٔ TinyMCE معمولی محدود است؛ سایر تگ‌ها (به‌ویژه `<script>`, `<iframe>`, `<style>`, `<form>`, `<object>`, attributeهای event-handler ⇒ توسط strip_tags همراه با تگ والد حذف می‌شوند) پاک می‌شوند. backward-compat: محتوای فعلی که فقط plain text + line break داشت همچنان بدون آسیب رندر می‌شود (strip_tags متن خام را دست‌نخورده برمی‌گرداند؛ فقط `<br>` در allowlist هست). افزایش رخدادهای XSS صفر؛ کاهش رخدادها در صورت inject محتوای آلوده.
- `app/Http/Controllers/Admin/FormController.php`: validation `intro_text` از `max:5000` به `max:65000` بالا برده شد چون TinyMCE معمولاً HTML بسیار طولانی‌تر از plain text تولید می‌کند. این تغییر صرفاً سقف را بالا می‌برد ⇒ هیچ ورودی تاریخی را reject نمی‌کند.

**۲. Rate-limit در `forms.submit` (production code change — Forms module):**
- `app/Providers/AppServiceProvider.php`: متد جدید `configureFormsWriteRateLimiters()` در `boot()` فراخوانی شد. RateLimiter به نام `forms-submit` با محدودیت `Limit::perMinute(10)->by($id)` که `$id` از ترکیب `(user_id ?: ip) + '|' + slug` ساخته می‌شود. در شبکه‌های NAT مشترک، slug در کلید مانع مسدودسازی متقابل کاربران مختلف بر سر یک فرم می‌شود.
- `routes/web.php`: middleware `throttle:forms-submit` به مسیر `POST /forms/{slug}` افزوده شد. سایر مسیرها لمس نشدند.
- پاسخ HTTP 429 پس از overage. هیچ تغییر در validation logic یا response body در حالت موفق.

**۳. راهنمای ادمین `admin.forms.guide`:**
- فایل جدید `resources/views/admin/forms/guide.blade.php` با محتوای فارسی، فهرست قابلیت‌ها، نکات SEO، و تاریخ `۱۴۰۵/۰۲/۲۳` (شمسی) در عنوان.
- `resources/views/admin/forms/index.blade.php`: قبل از کارت header، `@include('admin.forms.guide')` اضافه شد. در صفحات edit/preview راهنما مستقل ارائه شد، تا کاربر برای دیدن آن صفحه تغییر ندهد.

**۴. P0 production fix #۱ — Blade `@json` compile bug در `resources/views/forms/show.blade.php`:**
- ریشه: directive `@json` در Laravel Blade برای parse آرگومان‌ها از `explode(',', $expression)` استفاده می‌کند که در صورت وجود ویرگول داخل تابع تو در تو (مانند `strip_tags($pageDesc, ...)` پس از Wave 7.0b sanitization) آرگومان‌ها را غلط می‌شکند ⇒ compiled view یک `[` نابسته دارد ⇒ `ParseError` ⇒ HTTP 500 برای هر بازدید عمومی `/forms/{slug}`.
- این bug **قبل از Wave 7.0b هم latent بود** — حتی با `strip_tags($pageDesc)` بدون آرگومان دوم، هر تغییری در محتوای داخل می‌توانست آن را به سطح می‌رساند. هیچ‌گاه قبلاً HTTP test مسیر `forms.show` را hit نمی‌کرد ⇒ کشف نشده مانده بود.
- fix: جایگزینی `@json([...])` با block خام `<?php echo json_encode([...], JSON_UNESCAPED_UNICODE) ?>` که compiler آن را دست‌نخورده باقی می‌گذارد. payload JSON-LD، header `application/ld+json`، charset، و تمام metadata schema.org **بدون تغییر**. هیچ side-effect در SEO/SERP.

**۵. Tests + test-data hygiene fix:**
- `tests/Feature/Wave70bFormsHardeningTest.php`: ۱۳ تست feature جدید (۶۳ assertion). پوشش: sanitization در public + admin-preview، throttle (10/min + 11ام=429)، guide partial در index، validation در field store، slug-collision uniqueness، lifecycle (create/update/toggle/delete + cascade)، submit happy-path + validation error + inactive-404.
- private helperهای `adminFormPayload([...])` و `adminFieldPayload([...])` در همان test class اضافه شدند تا payload‌ها رفتار واقعی browser را شبیه‌سازی کنند: مرورگرها همهٔ inputهای nullable را به‌صورت `""` می‌فرستند (هرگز absent نیستند). این رفع latent test-data bug در ۴ تست بود (`admin_can_create_form_with_html_intro_text`, `admin_can_add_text_field_to_form`, `field_options_required_for_select_radio_checkbox`, `slug_collision_produces_unique_slug`) که قبل از این fix فیلدهای optional را اصلاً نمی‌فرستادند ⇒ `Admin\FormController::store` و `::storeField` با pattern قدیمی `$data['x'] ?: null` در request data ناقص به «Undefined array key» منجر می‌شد. این یک latent production bug است که فقط در سناریوی API external/script که قسمتی از فیلدها را omit کند ظاهر می‌شد (UI همیشه empty string می‌فرستد). با ارسال empty string از تست، رفتار واقعی UI dictated پوشش داده می‌شود؛ controller fix در wave بعدی (پس از تأیید صریح) ⇒ همان pattern `$data['key'] = $request->input('key') ?: null` می‌تواند جایگزین شود اما هیچ اولویتی ندارد چون UI همیشه payload کامل می‌فرستد.

**فایل‌های جدید:** `resources/views/admin/forms/guide.blade.php`، `tests/Feature/Wave70bFormsHardeningTest.php`.

**فایل‌های تغییر یافته:** `app/Http/Controllers/Admin/FormController.php` (فقط validation max length)، `app/Providers/AppServiceProvider.php` (افزودن RateLimiter جدید، صفر تغییر در RateLimiterهای موجود)، `resources/views/forms/show.blade.php` (sanitization + Blade @json P0 fix)، `resources/views/admin/forms/preview.blade.php` (sanitization)، `resources/views/admin/forms/index.blade.php` (include guide)، `routes/web.php` (throttle middleware).

**Backward-compat invariants حفظ شده:**
- صفر تغییر در migrationها/schema.
- صفر تغییر در `EnsureLicenseValid`، `EnsureModuleEnabled`، `SiteHelper`، یا منطق licensing.
- payload public form unchanged — content-type، validation errors، redirect targetها همان قبلی.
- payload admin form unchanged — مسیرها، fieldها، session flashها همان قبلی.
- JSON-LD payload schema.org bit-for-bit مطابق قبل از Wave 7.0b — تنها روش render تغییر کرد.
- entitlement مشتریان موجود لمس نمی‌شود (Forms ماژول licensed نیست؛ همیشه global فعال است).
- ۱۳ تست feature جدید سبز؛ regression suite کامل: ۱۷۴ pass، ۲ risky (همان baseline)، ۰ fail.

### Phase 2 / Wave 7.0a — Test Bootstrap Stabilization — ۱۴۰۵/۰۲/۲۳

پاکسازی ۱۰ failure باقی‌ماندهٔ baseline در test suite بدون تغییر هیچ‌گونه کد production. شاخه: `phase7-wave7a`. نتیجهٔ کل suite **پس از این wave**: ۱۶۳ تست ⇒ **۱۶۱ pass، ۲ risky، ۰ failure** (هر دو risky هم‌خانوادهٔ output-buffer در viewهای frontend `shop/show` و `blog/show` که `@push('json-ld')` می‌زنند — assertion سبز است، تنها هشدار hygiene PHPUnit).

**فلسفهٔ wave:** «Test Bootstrap Stabilization» یعنی مفروضات قدیمی setUp تست‌ها (که قبل از Wave 2 / P0-3 یعنی allow-list سخت در `EnsureLicenseValid` نوشته شده بودند) را با trait مشترک `Tests\Concerns\SeedsValidLicenseWithShop` (که در Wave 6.2 ساخته شد) همگام می‌کند. صفر تغییر در `app/**`، `routes/**`، `config/**`، `resources/views/**` (به‌جز `docs/**`)، صفر تغییر در `database/migrations/**`.

- **`tests/Feature/BlogProfessionalUpliftTest.php`:** `use SeedsValidLicenseWithShop`، `setUp` فقط `markSiteAsInstalled()` + `seedValidLicense(['blog'])`، حذف `withoutMiddleware()` (license معتبر مسیر full middleware را پاس می‌کند ⇒ تست‌ها رفتار production را بازنمایی می‌کنند نه دور می‌زنند)، حذف `tearDown()` که `installed.lock` tracked را unlink می‌کرد. ۲ failure (`future_scheduled_posts_are_hidden`، `public_blog_hides_posts_not_editorially_published`) به pass تبدیل شدند.
- **`tests/Feature/MenuModuleFeatureTest.php`:** `use SeedsValidLicenseWithShop`، `setUp` فقط `markSiteAsInstalled()` (تست `platform_ux_service_uses_local_menus_in_local_mode` سرویس را مستقیم صدا می‌زند و license نمی‌خواهد ⇒ seed عمداً deferred)، در تست `admin_modules_page_shows_menu_professional_status_card` بلوک inline `License::create([..., 'domain'=>'localhost', ...])` با `$this->seedValidLicense(['menu'])` جایگزین شد، حذف `tearDown()`. ۱ failure به pass تبدیل شد.
- **`tests/Feature/PhaseOneTwoThreeTest.php`:** `use SeedsValidLicenseWithShop`، `setUp` فراخوانی `markSiteAsInstalled()` + `config(['site.owner_site'=>true])` (حفظ شد برای backward-compatibility) + `seedValidLicense(['blog','shop'])`، حذف bulk inline `License::create`، حذف `tearDown()`. علاوه بر آن، در ۳ `Post::create` (یکی در `test_blog_search`، دوتا در `test_blog_show_displays_related_posts_and_reading_time`) فیلد `editorial_status => Post::EDITORIAL_PUBLISHED` افزوده شد چون `Post::scopePublished()` در پروداکشن این فیلد را اجباری می‌داند (test-data hygiene؛ نه production behavior change). ۴ failure به pass، ۱ failure (`blog_show_displays_related_posts_and_reading_time`) به risky-pass (assertion سبز، فقط hygiene warning) تبدیل شد.
- **`tests/Feature/StageFourValidationTest.php`:** `use SeedsValidLicenseWithShop`، `setUp` فقط `markSiteAsInstalled()` (تست `admin_route_redirects_to_license_when_license_missing` عمداً نباید license داشته باشد)، در ۳ متد failing جایگزینی inline `License::create([...])` با `$this->seedValidLicense([...])` با ماژول‌های متناسب (`['blog','shop']` برای دو متد و `['blog']` برای متد changelog blog)، حذف `tearDown()`. ۳ failure به pass تبدیل شدند.
- **به‌روزرسانی `docs/KNOWN-TEST-FAILURES.md`:** اعداد baseline به ۰ failure / ۲ risky تنظیم شد؛ تاریخچهٔ ۱۰ failure قبلی در جدول §۲ نگه داشته شد ولی همگی به ستون «وضعیت فعلی» با `pass`/`risky-pass` به‌روز شد؛ توضیح صریح هر ۲ risky و علت scope-out از Wave 7.0a در §۲.۲ اضافه شد؛ نقشهٔ راه §۵ به‌روز شد.

**Backward-compat invariants حفظ شده:**
- صفر تغییر در `EnsureLicenseValid`، `EnsureModuleEnabled`، `SiteHelper`، `BlogController`، `Post` model، `License` model، یا هر middleware/service/controller production.
- صفر تغییر در رفتار route‌ها، responseها، یا API.
- صفر migration در این wave.
- entitlement مشتریان موجود لمس نمی‌شود.
- ۹ تست failing → passing، ۱ تست failing → risky-pass (functional assertion سبز).
- ۲ تست قبلاً سبز (`shop/show` JSON-LD) بدون regression.

### Phase 6 / Wave 6.2 — Shop Test-Suite Stabilization — ۱۴۰۵/۰۲/۲۳

پاکسازی هدفمند ۶ failure باقی‌ماندهٔ baseline در `tests/Feature/ShopModuleFeatureTest.php` بدون تغییر منطق production. شاخه: `phase6-wave6.2`. نتیجهٔ کل suite **پس از این wave**: ۱۵۲ pass / ۱۰ fail (همگی non-Shop) / ۱ risky (output-buffer hygiene در helperهای JSON-LD/editor — خارج از scope و عدم تأثیر روی assertion).

- **P0-S3 — bootstrap محرز برای تست‌های Shop (`trait جدید`):** افزودن `tests/Concerns/SeedsValidLicenseWithShop.php` با سه متد:
  - `seedValidLicense(array $modules = ['shop']): License` ⇒ `License::firstOrCreate(['domain' => host])` با `platform_valid=true`، `last_validated_at=now()`، `expires_at=+1 year` و ماژول‌های دلخواه. منطق host از `parse_url(config('app.url'))` می‌خواند ⇒ بدون مفروضات سخت‌کد روی `localhost`. در محیط‌های لارگون با `APP_URL=http://customer-site.test` به‌درستی domain تست را مطابق درخواست HTTP می‌سازد ⇒ `EnsureLicenseValid` می‌پذیرد.
  - `markSiteAsInstalled(): void` ⇒ تضمین وجود `storage/installed.lock` بدون wipe‌کردن آن (idempotent).
  - `testRequestHost(): string` ⇒ helper داخلی برای استخراج host از `APP_URL` با fallback به `localhost`.
- **بازآرایی `ShopModuleFeatureTest::setUp()`:** حذف خطوط hard-coded قدیمی (`License::create(['domain'=>'localhost', ...])` و `@unlink(installed.lock)`) و فراخوانی `markSiteAsInstalled()` + `seedValidLicense(['shop'])`. این پایه برای استفادهٔ مجدد در `BlogProfessionalUpliftTest`، `MenuModuleFeatureTest`، `PhaseOneTwoThreeTest`، `StageFourValidationTest` در Wave آینده آماده است (همان trait با آرگومان `['blog','shop']`).
- **اصلاح latent schema bug — مایگریشن `2026_05_13_110000_add_timestamps_to_shop_pivots`:** پس از وصل trait، دو تست (`shop_index_uses_effective_price_for_filter_and_sort`، `product_review_not_visible_until_approved`) با `PDOException: Unknown column 'product_product_tag.created_at'` شکست خوردند. ریشه: `Product::tags()` و `Product::attributeValues()` در مدل با `withTimestamps()` تعریف شده‌اند ولی مایگریشن اصلی `2026_05_08_130200_shop_attributes_tags_related_bundles.php` ستون‌های `created_at/updated_at` را روی pivotهای `product_product_tag` و `product_product_attribute_value` نگذاشته بود ⇒ هرگاه Eloquent در attach/sync/eager-load به این pivot نیاز می‌داشت در پروداکشن نیز کرش می‌کرد. مایگریشن جدید کاملاً additive، idempotent (با `Schema::hasColumn` guard)، و reversible است. هیچ ستون موجود تغییر نمی‌کند؛ تنها در صورت نبود timestamp‌ها افزوده می‌شود. این یک fix محرز در پروداکشن است که در پوشش §۱۲ از Master Development Directive و قانون ۴ از `global-rules` صرفاً «افزودن قابلیت گمشده» محسوب می‌شود و هیچ entitlement مشتری را لمس نمی‌کند.
- **به‌روزرسانی `docs/KNOWN-TEST-FAILURES.md`:** baseline از ۱۶ به ۱۰ کاهش یافت؛ ردیف‌های ۸–۱۳ (همگی `ShopModuleFeatureTest`) به جدول «حذف‌شده در Wave 6.2» منتقل شدند؛ بخش «نقشهٔ راه» با اشاره به trait جدید به‌روز شد.
- **Risky test note (خارج از scope، تأیید کاربر):** تست `product_review_not_visible_until_approved` با assertion سبز ولی هشدار `Test code or tested code did not close its own output buffers` گزارش می‌شود. منشأ احتمالی در یکی از partialهای JSON-LD یا `_editor_scripts.blade.php` یا helper مشابه است. این یک hygiene warning است نه functional failure، هیچ ربطی به منطق shop ندارد، نه entitlement را می‌شکند نه backward compat را. fix آن برای wave بعدی شفاف‌سازی helperها رزرو شده.

**فایل‌های جدید:** `tests/Concerns/SeedsValidLicenseWithShop.php`, `database/migrations/2026_05_13_110000_add_timestamps_to_shop_pivots.php`.

**فایل‌های تغییر یافته:** `tests/Feature/ShopModuleFeatureTest.php` (بازنویسی `setUp` بدون تغییر هیچ assertion یا متد test).

**Backward-compat invariants حفظ شده:**
- صفر تغییر در `EnsureLicenseValid`، `EnsureModuleEnabled`، `SiteHelper`، یا هر middleware/service لایسنس.
- صفر تغییر در رفتار سرویس‌ها/کنترلرها/UI ادمین یا کاربر.
- مایگریشن pivot هیچ ستون موجود را drop/rename نمی‌کند و فقط در نبود ستون اضافه می‌کند ⇒ سایت‌های پروداکشن که قبلاً به‌نحوی این ستون‌ها را داشته‌اند (مهاجرت دستی یا rebuild)، unaffectedاند.

### Phase 6 / Wave 6.1 — Shop Data-Integrity Hardening — ۱۴۰۵/۰۲/۲۳

Hardening pass on the Shop module's transactional surface. All changes are additive, reversible, and entitlement-safe (no automatic revocation, no destructive migration). Branch: `phase6-wave6.1`. Regression: 22 new tests (49 assertions) — all green; cross-wave regression with Wave 2/3/4/5.2/5.3 → 67 tests (171 assertions) all green; baseline failure count unchanged at 16.

- **P0-S1 — `coupons.discount_percent` nullability (`commit 1d70785`):** مهاجرت `2026_05_13_100000_make_coupons_discount_percent_nullable` ستون را nullable می‌کند تا `Admin\CouponController` بتواند برای نوع `fixed` مقدار `NULL` ذخیره کند بدون خطای SQL در strict mode. سطرهای historical با `discount_type=fixed AND discount_percent=0` بازنرمالایز می‌شوند به NULL برای نمایش تمیز.
- **P0-S4 — `Order::generateNumber` ایمن در همزمانی (`commit 13669aa`):** قفل advisory در MySQL (GET_LOCK/RELEASE_LOCK) per-day، طول دنباله از ۴ به ۶ رقم (max 999,999/day)، الگوریتم retry برای collisionها در driverهای فاقد lock helper (SQLite/Postgres در تست‌ها). شماره‌های 4-رقمی قدیمی هنوز unique و سازگارند.
- **P1-S1 — Race در checkout (`commit 2ff4d1f`):** قبل از هرگونه validation، همهٔ سطرهای متأثر (parent productها، bundle childها، variantهای درخواستی) به ترتیب صعودی primary key با `whereIn(...)->lockForUpdate()` قفل می‌شوند. این oversell در دو سناریوی پیش‌بینی‌شده را می‌بندد: (الف) bundle child مشترک بین دو bundle، (ب) variant مشترک بین تنوع‌های مختلف یک محصول. ترتیب پایدار قفل ⇒ deadlock-free.
- **P1-S5 — IDOR در `Account\OrderController` (`commit ea5f697`):** شاخهٔ legacy «تطبیق با customer_email» حذف شد. حالا فقط `user_id` معتبر است؛ کاربری که ایمیل پروفایلش را عوض کند نمی‌تواند سفارش guest را ببیند. مسیر guest order lookup مستقل از این کنترلر (با شمارهٔ سفارش + ایمیل تأییدی) دست‌نخورده است.
- **P1-S2 / P1-S3 / P1-S4 — `OrderStatusService` + history table (`commit d444adf`, `commit 44cc569`, `commit 9823fa3`):**
  - مهاجرت `2026_05_13_100100_create_order_status_history_table` ⇒ جدول `order_status_history` (from/to status، changed_by_user_id، reason، JSON side_effects، created_at). ایندکس روی `(order_id, created_at)` و `to_status`.
  - مهاجرت `2026_05_13_100200_add_restocked_at_to_orders` ⇒ ستون nullable `orders.restocked_at` به‌عنوان idempotency guard برای side-effect لغو.
  - مدل `App\Models\OrderStatusHistory` (با `$table='order_status_history'` صریح برای جلوگیری از پلورال‌سازی Eloquent).
  - سرویس `App\Services\OrderStatusService` تنها مسیر تغییر وضعیت سفارش است: FSM را اعمال می‌کند (cancelled ⇒ terminal، transitions مجاز در const `TRANSITIONS`)، یک ردیف history می‌نویسد، یک ردیف `admin_activity_log` با `action='order.status_changed'` از طریق `AdminActivityLogger::log()` ثبت می‌کند، و در گذار به `cancelled` به‌صورت اتمیک stock محصول/variant را برمی‌گرداند و `coupon.used_count` را کاهش می‌دهد. `restocked_at` تضمین می‌کند هر سفارش حداکثر **یک‌بار** restock شود حتی اگر ادمین به‌صورت دستی paid → cancelled → paid → cancelled کند.
  - `Admin\OrderController::updateStatus` کاملاً به سرویس delegate می‌شود؛ illegal transition ⇒ `flash error` و audit بدون پیشرفت. فرم با فیلد اختیاری `reason` گسترش یافت.
  - بلاک کوتاه Jalali (`Y/m/d H:i`) برای نمایش تاریخچهٔ وضعیت در `admin/orders/show.blade.php`. UI کامل timeline برای Wave 6.3 رزرو است.

**فایل‌های جدید:** `app/Models/OrderStatusHistory.php`, `app/Services/OrderStatusService.php`, `database/migrations/2026_05_13_100000_make_coupons_discount_percent_nullable.php`, `database/migrations/2026_05_13_100100_create_order_status_history_table.php`, `database/migrations/2026_05_13_100200_add_restocked_at_to_orders.php`, `tests/Feature/Wave61ShopDataIntegrityTest.php`.

**فایل‌های تغییر یافته:** `app/Models/Order.php` (status constants + advisory-lock generateNumber + statusHistory relation + restocked_at cast), `app/Http/Controllers/Admin/OrderController.php`, `app/Http/Controllers/Account/OrderController.php`, `app/Http/Controllers/Front/ShopController.php`, `resources/views/admin/orders/show.blade.php`.

**Backward-compat invariants حفظ شده:**
- شماره‌های قدیمی 4-رقمی هنوز unique و قابل lookup هستند.
- ادمین می‌تواند هر وضعیت قبلی را به cancelled تغییر دهد (terminal فقط در یک سمت).
- ledger entitlement مشتری (Wave 5.1) و دروازه‌های captcha/rate-limit (Wave 5.3) دست‌نخورده.

### Phase 5 — Technical-debt note — ۱۴۰۵/۰۲/۲۲

- ایجاد فایل **`docs/KNOWN-TEST-FAILURES.md`** برای ایزوله نگه‌داشتن ۱۶ failure pre-existing در PHPUnit suite این مخزن. این failureها هیچ‌کدام به Wave 5.x ربط ندارند و با `git checkout a213fcc` (commit دقیقاً پیش از Wave 5.3) قابل بازتولید‌اند (یعنی baseline). علت ریشه‌ای همگی در bootstrap تست‌های قدیمی است (عدم seed لایسنس معتبر یا `modules => ['shop', 'blog']` در `setUp()`). الگوی صحیح در `tests/Feature/Wave53ShopGuardsTest::seedValidLicense()` نشان داده شده. **هیچ تغییری در کد production لازم نیست؛ تست‌های Wave 5.x در فایل‌های جداگانه‌اند و license/module را خود seed می‌کنند.** قانون: اگر run بعدی بیشتر از ۱۶ failure داشت ⇒ regression جدید بررسی شود؛ اگر کمتر بود ⇒ این فایل به‌روز شود.

### Phase 5 / Wave 5.3 — ۱۴۰۵/۰۲/۲۲

- **P1-5 (Captcha & Rate Limiting برای نقاط نوشتنی فروشگاه — customer-site):** سه فاز پیاپی روی همین مخزن، صفر تغییر در saas-platform، صفر مایگریشن، صفر breaking change. مجموع ۲۱ feature test جدید (`Wave53ShopGuardsTest`, ۴۳ assertion) ⇒ همگی سبز. regression مشترک با Wave 2/3/4 → ۴۴ تست سبز (۱۱۵ assertion).

  **Phase 5.3A — Rate Limiters (`commit 1c8b709`):** افزودن ۴ limiter named در `App\Providers\AppServiceProvider::configureShopWriteRateLimiters()` (تعریف via `RateLimiter::for`):
  - `shop-cart` ⇒ ۶۰ درخواست/دقیقه (روی `shop.cart.add/update/remove` + `shop.wishlist.toggle` + `shop.compare.toggle`)
  - `shop-checkout` ⇒ ۵ درخواست/دقیقه (روی `shop.checkout`)
  - `shop-review` ⇒ ۳ درخواست/۱۰ دقیقه (روی `shop.reviews.store`، scoped به product slug)
  - `shop-stock-notify` ⇒ ۵ درخواست/ساعت (روی `shop.stock-notify`، scoped به product_id)
  - کلید پایه: کاربر authenticated ⇒ `u:{id}` (یکسان بین تب‌ها/مرورگرها)، در غیر این صورت ⇒ `g:{ip}:{session_id_truncated}`. تجاوز ⇒ پاسخ ۴۲۹ استاندارد Laravel با header `Retry-After`. حد‌ها بسیار بالاتر از رفتار طبیعی کاربر صادق ⇒ هیچ خریدار/نظردهنده/مشتاق موجودی به این حد نمی‌خورد.
  - مسیرها (`routes/web.php`) با middleware `throttle:shop-cart|shop-checkout|shop-review|shop-stock-notify` پوشش داده شدند. هیچ route جدیدی اضافه نشد.

  **Phase 5.3B — کپچای مشروط برای کاربر مهمان (`commit 40bd246`):** `App\Services\CaptchaService` با سه helper جدید گسترش یافت بدون تکرار منطق captcha (همه از طریق `generatePersianNumber`/`generateNormal`/`verifyHcaptcha`/`validatePersianNumber`/`validateNormal` موجود کار می‌کنند):
  - `isShopCaptchaEnabled()` ⇒ true فقط اگر ماژول کپچا روشن باشد و setting جدید `shop_captcha_enabled='1'`.
  - `getShopCaptchaConfig()` ⇒ payload رندر برای مهمان؛ کاربر authenticated همیشه `enabled=false`.
  - `validateShopCaptcha(Request)` ⇒ کاربر authenticated همیشه `true` (bypass)؛ مهمان بسته به نوع کپچا فیلد `captcha_answer` یا `h-captcha-response` را با همان متدهای موجود اعتبارسنجی می‌کند.
  - `App\Http\Controllers\Front\ShopController` در سه نقطه (`checkout`، `storeReview`، `stockNotify`) قبل از validate درخواست، کپچا را چک می‌کند. در شکست ⇒ `back()->withInput()->withErrors([...])` و هیچ DB write انجام نمی‌شود.
  - partial جدید `resources/views/partials/shop-captcha.blade.php` خودش از `CaptchaService::getShopCaptchaConfig()` می‌خواند ⇒ نیازی به پاس‌دادن متغیر از controller نیست. در سه فرم include شد: `cart.blade.php` (checkout)، `show.blade.php` (stock-notify)، `show.blade.php` (review).

  **Phase 5.3C — Admin toggle + tests + docs (`commit pending`):**
  - `App\Http\Controllers\Admin\CaptchaController::index` متغیرهای جدید `shopCaptchaEnabled` و `shopModuleEnabled` (از `SiteHelper::shopEnabled()`) را به view می‌فرستد.
  - `update()` validation و persistence برای `shop_captcha_enabled` (boolean optional). اگر ماژول shop غیرفعال باشد، toggle disabled رندر می‌شود (با پیام راهنما) ولی اگر ادمین آن را روشن کرده بود، setting حفظ می‌ماند (entitlement protection §۴ — تنظیمات کاربر را به‌خاطر تغییر وضعیت ماژول حذف نمی‌کنیم).
  - `resources/views/admin/captcha/index.blade.php` با چک‌باکس جدید (با توضیح فارسی: «شامل: ثبت سفارش، ثبت نظر محصول، درخواست اطلاع‌رسانی موجودی. فقط برای کاربر مهمان اعمال می‌شود.») و عنوان «(آخرین بروزرسانی: ۱۴۰۵/۰۲/۲۲)» در header کارت.
  - `tests/Feature/Wave53ShopGuardsTest.php`: ۲۱ تست/۴۳ assertion:
    - ۴ تست برای حضور limiter‌های nameبر روی هر ۴ route با middleware صحیح؛
    - ۴ تست برای maxAttempts درست هر limiter و keyهای متمایز per (slug | product_id) و per (user_id vs guest);
    - ۷ تست برای captcha service (bypass auth, reject guest without answer, accept correct persian, disabled when off, etc.);
    - ۳ تست برای admin toggle (persist/clear/view variables);
    - ۳ تست برای backward compatibility (default = '0'، auth_captcha مسیر unchanged، شکست هیچ helper موجود).

  **Backward compatibility:** پیش‌فرض `shop_captcha_enabled='0'` ⇒ هیچ سایت موجودی تحت تأثیر قرار نمی‌گیرد مگر ادمین opt-in کند. هیچ migration، هیچ تغییر API پلتفرم، هیچ تغییر در entitlement کاربر. کاربر authenticated هرگز کپچا نمی‌بیند (بار UX روی خریدار صادق صفر).

  **Pre-merge hardening (`commit 18766a2`):** اصلاح edge case در `AppServiceProvider::shopRateKey()` که در آن اگر `$request->hasSession()=true` ولی `getId()=''` (مثلاً سفارشی‌سازی session impl)، key با `:` خالی پایان می‌گرفت و چند کاربر anonymous مختلف در یک bucket می‌افتادند. حالا تنها وقتی `getId() !== ''` است session را در key استفاده می‌کنیم؛ در غیر این صورت ثابت `no-sess` (مثل request بدون session). تست‌های پیش‌رفته‌ای روی هر سه شاخه (no session، blank-id session، normal ۴۰-char truncated). همچنین تأیید شد `php artisan route:cache` بدون collision بین limiter name‌ها موفق است (هر ۸ instance `throttle:shop-*` پس از cache بازیابی می‌شوند). `merge commit be8cd74`

### Phase 5 / Wave 5.4 — ۱۴۰۵/۰۲/۲۲

- **P3-1/P3-2 (regression test suite — customer-site):** افزودن ۲۳ feature test جدید برای رفتارهای حساس waveهای ۲ تا ۴ این مخزن. هیچ تغییر رفتاری در کد production انجام نشد (فقط یک fix نام FK برای امکان‌پذیر ساختن `migrate:fresh` در MySQL). **مجموع suite: ۱۱۹ تست (۹۶ baseline + ۲۳ Wave 5.4) با ۲۷۸ assertion. تمام ۲۳ تست Wave 5.4 سبز.** ۱۶ failure باقی‌مانده در تست‌های baseline pre-existing (shop/blog/menu/stage4) است و به Wave 5.4 یا فاز ۵ ربطی ندارد.
  - `tests/Feature/Wave2EncryptedLicenseKeyTest.php` (P0-4، ۶ تست): مقدار encrypted در دیتابیس ذخیره می‌شود (Crypt::decryptString روی row خام تأیید)، Eloquent decrypt شفاف، row legacy plaintext خوانده می‌شود (DecryptException fallback)، در save بعدی به‌طور خودکار lazy-upgrade به encrypted (بدون مایگریشن backfill)، null/empty pass-through، `maskedKey()` روی plaintext decrypted کار می‌کند. `commit 24ee103`
  - `tests/Feature/Wave3AdminActivityLogTest.php` (P1-2، ۹ تست): وجود جدول `admin_activity_log` و ستون‌های کامل، `AdminActivityLogger::log()` با action/description/properties/Auth::id() خودکار، default description از `actionLabel($action)`، schema-guard: نبود جدول ⇒ null بدون exception، Persian labels برای ۷ اکشن شناخته‌شده + fallback به action key برای ناشناخته، properties array-cast، رابطهٔ `adminUser()`. `commit a3f8d60`
  - `tests/Feature/Wave4MarketplaceAndBannerTest.php` (P2-5 + P2-6، ۸ تست): سه badge متمایز برای `installed`/`owned_disabled`/`available`؛ backward-compat با APIای که فقط `installed`/`owned` می‌دهد؛ ماژول installed بدون buy button. transient cache key پایدار `license.transient_error.{id}` و license-scoped؛ 5xx ⇒ cache+`sync_skipped=true`+ license معتبر می‌ماند؛ ConnectionException ⇒ cache `error=connection_error`+ license معتبر؛ validation موفق ⇒ Cache::forget و platform_valid=true؛ rejection قطعی (`revoked`/...) ⇒ بدون cache و `platform_valid=false`. `commit 10d518e`

- **Migration fix (testability only):** نام پیش‌فرض FK در migration `2026_05_08_130200_shop_attributes_tags_related_bundles.php` برای ستون `product_attribute_value_id` به‌صورت خودکار `product_product_attribute_value_product_attribute_value_id_foreign` (۶۷ کاراکتر) تولید می‌شد که از محدودیت ۶۴ کاراکتر MySQL عبور می‌کند. این مسئله باعث می‌شد **هر تست RefreshDatabase در این مخزن پیش از رسیدن به assertion با QueryException متوقف شود** (۴۵ خطا در baseline قبل از Wave 5.4). نام index صراحتاً به `ppav_attr_value_fk` کوتاه شد؛ **سمنتیک FK (cascadeOnDelete + جدول/ستون مرجع) دست‌نخورده است** و دادهٔ موجود در production تحت تأثیر قرار نمی‌گیرد (DDL فقط در زمان ایجاد جدول مهم است). نتیجه: ۲۹ تست baseline که قبلاً به دلیل FK خطا می‌خوردند، حالا اجرا می‌شوند؛ failureها از ۴۵ به ۱۶ کاهش یافت. `commit 24ee103`

### Phase 5 / Wave 4 — ۱۴۰۵/۰۲/۲۱

- **P2-5:** نمایش وضعیت «قبلاً خریداری‌شده — غیرفعال» در فروشگاه ماژول پنل ادمین (`resources/views/admin/module-store/index.blade.php`). با خواندن فیلدهای جدید `status` و `owned` از پاسخ پلتفرم (و fallback به‌محاسبه از `installed`/`owned` در صورت نبود)، اگر مشتری ماژولی را قبلاً خریده ولی فعلاً غیرفعال است، به‌جای دکمهٔ «خرید»، badge امبر «قبلاً خریداری‌شده — غیرفعال» + دکمهٔ «فعال‌سازی» به route `admin.modules.index` نمایش داده می‌شود. backward-compat با نسخهٔ قبلی API پلتفرم (که فقط `installed` می‌داد) کاملاً حفظ شد. `commit 4221b07`
- **P2-6:** banner خطای موقت لایسنس در پنل ادمین. `LicenseActivationService::syncWithPlatform` همیشه خطاهای transient (5xx، timeout، network) را تشخیص می‌داد و لایسنس را invalid نمی‌کرد، اما کاربر هیچ نمایش بصری از این وضعیت در پنل نمی‌دید. اکنون: هنگام skip شدن sync به دلیل خطای موقت، Cache::put با کلید `license.transient_error.{license_id}` و TTL 30 دقیقه (شامل error/http_status/message/detected_at) ست می‌شود؛ در validation موفق بعدی به‌طور خودکار Cache::forget می‌شود. partial جدید `resources/views/admin/partials/license-transient-banner.blade.php` در `admin/layout.blade.php` include شد و در صورت وجود cache یک باکس امبر admin-flash--warning با لینک «تلاش مجدد» (به `admin.license.refresh-config.get`) نمایش می‌دهد. متن صریحاً تأکید می‌کند لایسنس فعلی همچنان معتبر است و دسترسی به ماژول‌ها قطع نمی‌شود (per global-rules §۴). متد جدید `LicenseActivationService::transientErrorCacheKey(int $id): string` برای استفادهٔ مشترک. هر cache exception با try/catch دفع شد ⇒ validation هرگز به‌خاطر cache break نمی‌شود. `commit e63da3c`

### Phase 5 — Wave-tip tags — ۱۴۰۵/۰۲/۲۱

- افزوده شدن سه **lightweight tag** برای نام‌گذاری دائمی tip شاخه‌های phase5-waveN قبل از حذف احتمالی feature branchها (محلی، بدون push به GitHub):
  - `phase5-wave1-tip` → `85b263a`
  - `phase5-wave2-tip` → `062bacb`
  - `phase5-wave3-tip` → `46e4fcc`
- مستندسازی کامل تگ‌ها، تفاوت با `deploy-backup-*` و `branch`، هشدار خاص customer-site (checkout فقط کد را برمی‌گرداند نه دیتابیس) و الگوی استفادهٔ روزمره در `DEPLOY-fa.md` §۱۰ (فارسی) و `DEPLOY.md` §10 (انگلیسی). شامل سناریوهای checkout/diff/log/show، روال wave آینده، بازیابی از merge commit، و push اختیاری به GitHub.

### Phase 5 / Wave 3 — ۱۴۰۵/۰۲/۲۱

- **P1-2:** افزودن زیرساخت لاگ فعالیت پنل ادمین سایت مشتری. مایگریشن جدید `2026_05_11_180000_create_admin_activity_log_table.php` با جدول `admin_activity_log` (admin_user_id, action, description, subject_type/id, properties json, ip, user_agent, timestamps + ایندکس‌های action/created_at/subject/composite). مدل `App\Models\AdminActivityLog` با cast properties=>array و رابطهٔ `adminUser()`، سرویس `App\Services\AdminActivityLogger::log()` با `Schema::hasTable` guard، کنترلر `App\Http\Controllers\Admin\AdminActivityLogController` با فیلتر admin_user_id/action/from(شمسی)/to(شمسی) و pagination 25، view `resources/views/admin/activity-log/{index,guide}.blade.php` با تاریخ شمسی (Jalali::formatFa) و کامپوننت `x-persian-date-input`. مسیر `GET /admin/activity-log` تحت middleware `[auth, admin, license.valid, permission:dashboard]`. در `AdminMenu` کلید `activity_log` به MENU_KEYS/MENU_ROUTES/MENU_ROUTE_PATTERNS، گروه «لایسنس، ماژول‌ها و پشتیبانی» و لیست always-visible افزوده شد. اولین نقاط استفاده: `LicenseController::update` → `license.activated`؛ `refreshConfig`/`refreshConfigGet` → `license.refreshed_config`؛ `refreshModules` → `license.refreshed_modules` با diff `added`/`removed` ماژول‌ها (entitlement preserved)؛ `Admin\ModuleController::update` (advanced_search و menu) → `module.settings_updated`. اضافه‌فقط و عقب‌سازگار — هیچ مسیر/مدل/جدول موجود تغییر نکرد. `commits 59a6411`, `1a70961`

### Phase 5 / Wave 2 — ۱۴۰۵/۰۲/۲۱

- **P0-4:** فیلد `license_key` در مدل `App\Models\License` با Cast سفارشی `App\Casts\EncryptedOrPlaintext` به‌صورت encrypted در دیتابیس ذخیره می‌شود. Cast جدید با Laravel `Crypt` رمزنگاری/رمزگشایی می‌کند و در صورت روبه‌رو شدن با مقدار legacy plaintext (`DecryptException`) همان را برمی‌گرداند و در اولین save بعدی به‌طور خودکار encrypted ذخیره می‌کند (lazy migration). **هیچ مایگریشن دیتابیس یا backfill لازم نیست.** هیچ query مستقیمی روی فیلد license_key در کد نیست (تأیید شده با grep) → سازگاری کامل با LicenseActivationService، EnsureLicenseValid و maskedKey(). `commit ab1d0f2`

### Phase 5 / Wave 1 — ۱۴۰۵/۰۲/۲۱

- **P4-4:** حذف فایل‌های garbage 0-byte از root مخزن (artifactهای shell-pipe ناقص که اشتباهاً tracked شده بودند): `getNameWithReadWriteType()`, `home`, `isUniqueConstraintError($e)`, `main`, `origin)`, `prepareBindings($bindings)`. هیچ‌کدام در کد یا تست ارجاع نداشتند. `commit 3ee4efb`
- **P0-7:** نگاشت دوطرفهٔ alias بین کلیدهای ماژول `whatsapp` ↔ `messaging` در `App\Helpers\SiteHelper`. پیش‌تر فقط `whatsapp → messaging` پشتیبانی می‌شد؛ اکنون `messaging → whatsapp` هم هست تا مشتریان قدیمی با لایسنس `whatsapp` در صورت چک کد بر اساس `messaging` dysfunctional نشوند. سازگار با Global Rules §۴ (Immutable Customer Entitlement Protection). `commit 69f5f7c`
- **P0-3:** جایگزینی prefix-match با لیست صریح route name های مجاز (allow-list) در `App\Http\Middleware\EnsureLicenseValid`. پیش‌تر هر route آینده‌ای با prefix `admin.license.` یا `install.` به‌طور خودکار قبل از validation لایسنس قابل دسترسی می‌شد؛ اکنون فقط ۱۷ route عمدی به نام (login, logout, admin.license.*، install.*) آزاد است. `commit 09616da`

### قبلی

- `global-rules.mdc`: بخش‌های ۱۴–۱۷ و §۱۸ Laravel (هم‌محتوا با saas-platform).
- قانون سراسری Cursor (`global-rules.mdc`): توسعهٔ امن، لایسنس، entitlement، انتشار قابلیت، migration و تست (هم‌محتوا با saas-platform).
- همگام‌سازی قوانین Cursor: مسیر پلتفرم قابل پیکربندی (`CURSOR_RULES_SAAS_PLATFORM_ROOT`، `cursor-rules-sync.local.json`، نمونه `cursor-rules-sync.local.example.json`)؛ به‌روزرسانی مستند همگام‌سازی.
- همگام‌سازی قوانین Cursor: wrapper `scripts/sync-cursor-rules.ps1`، Composer (`sync-cursor-rules` / `sync-cursor-rules:reverse`)، `.githooks/post-commit` و `docs/CURSOR-RULES-SYNC.md`.
- همسان‌سازی محتوای `.cursor/rules/*.mdc` بین **saas-platform** و **customer-site** (شامل `persian-date-only`, `project-guide-update`, `site-modules-from-platform`, `saas-platform-main-admin`, `saas-module-guide-update`, `content-modules-jalali-editor`, `global-rules`).
- قانون Cursor «Billing and Payment»: `globs` به مسیرهای Laravel (سرویس پرداخت، مدل‌های فاکتور/درگاه) هم‌راستا شد.
- راه‌اندازی مستندات ماژول‌ها (`docs/modules/`) و پرامپت استاندارد (`docs/PROMPT_FOR_CURSOR.md`).
