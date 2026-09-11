# تست و کیفیت سازندهٔ بصری (Builder QA)

> Referenced by `saas-platform/docs/quality/TESTING_AND_DELIVERY_GOVERNANCE.md`
> §4 as the canonical spec of Builder behavior to verify. The manual
> checklist below (`BUILDER-TEST-CHECKLIST.md`) is the interim procedure;
> the governance document's Builder Delivery Contract is the plan to make
> save→reload→preview→public-render automated-first via
> `tests/browser/smoke.spec.js` and its siblings, per capability, rather
> than a routine manual pass.

این سند خلاصهٔ سیستم تست و رندر امن سازندهٔ بصری است.

---

## Element SDK (سیستم رسمی المان‌ها)

همهٔ المان‌های سازنده روی **Element SDK** استاندارد شده‌اند.

- **مسیر:** `app/Builder/` — Contracts، Elements (BaseElement + المان‌های مشخص)، ElementRegistry، Validation/ElementValidator، Rendering/ElementRenderer، Services/DynamicDataResolver، Testing/ElementTestSuite
- **پیکربندی:** `config/builder.php` (لیست کلاس‌های المان)
- **رندر:** برای هر بلوک، اگر نوع در رجیستری باشد، BlockRenderer از ElementRenderer استفاده می‌کند.
- **تست المان‌ها:** `php artisan builder:test-elements`

---

## فاز ۱ — قرارداد المان (Element Contract)

- **فایل‌ها:** `config/builder-element-schemas.php` ، `app/Services/ThemeBuilder/ElementSchemaRegistry.php`
- هر نوع المان (heading، text، image، button، section، …) یک **schema** دارد: نوع، props الزامی/اختیاری، نوع داده، محدودیت طول (رشته، URL) و عمق فرزندان.
- **قبل از رندر** هر بلوک با `ElementSchemaRegistry::validate($block)` اعتبارسنجی می‌شود.
- اگر **نامعتبر** باشد: خطا در لاگ ثبت می‌شود، رندر انجام نمی‌شود و به‌جای آن **placeholder** نمایش داده می‌شود.

---

## فاز ۶ — رندر امن (Error Boundary)

- داخل `BlockRenderer::renderBlock()`:
  1. اعتبارسنجی با schema.
  2. رندر واقعی داخل `try/catch`.
- اگر **exception** رخ دهد: خطا در لاگ با `element_id` و `type` ثبت می‌شود و به‌جای المان یک **placeholder** با کلاس `tb-placeholder` و `data-type` / `data-reason` نمایش داده می‌شود تا کل صفحه خراب نشود.

---

## تست خودکار

- **Unit – اعتبارسنجی:** `tests/Unit/ThemeBuilder/ElementSchemaValidationTest.php`  
  موارد: نوع معتبر، props خالی، مقادیر اختیاری حذف‌شده، متن/URL خیلی طولانی، نوع نامعتبر، عمق تو در تو.
- **Unit – رندر المان‌ها:** `tests/Unit/ThemeBuilder/BlockRendererElementTest.php`  
  موارد: رندر پیش‌فرض، props خالی، تصویر بدون URL، دکمه، سکشن تو در تو، schema نامعتبر → placeholder، `renderBlocks` چند بلوک و خالی.

اجرای تست‌ها (در محیطی که `php` در PATH باشد):

```bash
php vendor/bin/phpunit tests/Unit/ThemeBuilder/
# یا
composer test -- --filter=ThemeBuilder
```

---

## چک‌لیست تست دستی

چک‌لیست برای تست دستی سازنده تا اینجای کار در **docs/BUILDER-TEST-CHECKLIST.md** قرار دارد.

---

## فاز ۳ — Snapshot رندر

- **فایل:** `tests/Unit/ThemeBuilder/ElementSnapshotTest.php`
- خروجی رندر heading، text، button و یک section با heading تو در تو با فایل‌های snapshot در `tests/fixtures/builder-snapshots/*.html` مقایسه می‌شود. در اجرای اول snapshot ساخته می‌شود؛ هر تغییر غیرمنتظرهٔ HTML تست را fail می‌کند.

---

## فاز ۴ — استرس

- **فایل:** `tests/Unit/ThemeBuilder/BuilderStressTest.php`
- ساخت ۵۰ بخش (هر کدام row + دو column + heading/text/button)، رندر با `BlockRenderer::renderBlocks()`، اطمینان از نبود exception، وجود محتوا و زمان رندر کمتر از ۵ ثانیه؛ به‌اضافهٔ تست برای ساختار عمیقاً تو در تو.

---

## فاز ۵ — سناریو

- **فایل:** `tests/Feature/BuilderScenarioTest.php`
- با RefreshDatabase و WithoutMiddleware: ساخت Page و User ادمین، ارسال PUT با `builder_content_v2` (یک section با دو column و heading + text)، بررسی پاسخ موفق، یکپارچگی بعد از reload و وجود «عنوان سناریو» و «متن سناریو» در خروجی رندر.

---

## فاز ۷ — داشبورد سلامت المان‌ها

- **مسیر ادمین:** `admin/builder-health` (منو: سیستم → سلامت المان‌های سازنده)
- **کنترلر:** `App\Http\Controllers\Admin\BuilderHealthController`
- **ویو:** `resources/views/admin/builder-health/index.blade.php`
- با هر بار باز کردن صفحه، `ElementTestSuite::run()` اجرا می‌شود و نتیجه (موفق/ناموفق به‌تفکیک نوع المان، جدول وضعیت) نمایش داده می‌شود. برای اجرای مجدد تست‌ها صفحه را رفرش کنید یا از دستور `php artisan builder:test-elements` استفاده کنید.

---

## فاز ۸ — محیط تست با overlay دیباگ

- **مسیر:** `GET /builder/test` (نیاز به احراز هویت و دسترسی ادمین)
- سازنده با **دادهٔ فیک** (یک بخش، دو ستون، عنوان + متن + دکمه) باز می‌شود؛ دکمهٔ ذخیره غیرفعال است.
- **Overlay دیباگ** (پایین چپ): تعداد المان‌ها، لیست شناسهٔ المان‌ها، زمان رندر (ms) و حافظهٔ پیک (B/KB/MB) که از endpoint پیش‌نمایش با پارامتر `_test_mode=1` برگردانده می‌شود.

---

## فازهای بعدی (طراحی)

- **فاز ۲ (تست واحد گسترده‌تر):** موبایل، hover، انیمیشن، بدون استایل، فرزند عمیق — در صورت نیاز به‌صورت تدریجی به تست‌های واحد اضافه می‌شوند.

---

*آخرین به‌روزرسانی: ۱۴۰۴/۱۲/۰۹*
