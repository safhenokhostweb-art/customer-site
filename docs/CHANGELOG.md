# Changelog

تمام ورودی‌ها به‌صورت معکوس زمانی (جدیدترین بالا). فقط هنگام تغییر واقعی روی ماژول‌ها/قابلیت‌ها به‌روز شود.

## [Unreleased]

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
