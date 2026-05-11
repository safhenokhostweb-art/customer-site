# Changelog

تمام ورودی‌ها به‌صورت معکوس زمانی (جدیدترین بالا). فقط هنگام تغییر واقعی روی ماژول‌ها/قابلیت‌ها به‌روز شود.

## [Unreleased]

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
