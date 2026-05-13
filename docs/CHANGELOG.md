# Changelog

تمام ورودی‌ها به‌صورت معکوس زمانی (جدیدترین بالا). فقط هنگام تغییر واقعی روی ماژول‌ها/قابلیت‌ها به‌روز شود.

## [Unreleased]

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
