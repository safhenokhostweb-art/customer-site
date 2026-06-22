# CUSTOMER MENU BEFORE / AFTER
> Generated: 2026-06-22

---

## BEFORE (11 groups, 24 description-only dynamic modules could pollute sidebar)

```
اصلی
  └── داشبورد

محتوا و ساختار سایت
  ├── صفحات
  ├── منو و ناوبری          (moduleEnabled)
  ├── دسته‌بندی‌های وبلاگ   (blogEnabled)
  ├── مجموعه‌های مطالب      (blogEnabled)
  ├── مطالب وبلاگ           (blogEnabled)
  └── جستجوی پیشرفته       (moduleEnabled)

فروشگاه
  ├── دسته‌بندی محصولات     (shopEnabled)
  ├── محصولات               (shopEnabled)
  ├── ویژگی‌های فنی محصول  (shopEnabled)
  ├── نظرات خریداران        (shopEnabled)
  ├── سفارشات               (shopEnabled)
  ├── کوپن‌ها               (moduleEnabled)
  └── خرید قسطی            (moduleEnabled)

پشتیبانی و مدیریت کاربران
  ├── صندوق پشتیبانی
  └── پنل کاربران

فرم‌ها و ارتباط با مخاطب
  ├── فرم‌ساز               (moduleEnabled)
  ├── گزارش ارسال فرم‌ها    (moduleEnabled)
  ├── لیست‌های دسترسی       (moduleEnabled)
  ├── نظرات و امتیازدهی    (moduleEnabled)
  ├── معرفی دوستان          (moduleEnabled)
  ├── پاسخگویی شبکه‌ها     (moduleEnabled)
  ├── پیامک                 (moduleEnabled)
  ├── کمپین‌های پیامکی      ⚠️ NEVER SHOWN (isVisible bug)
  └── اعلان‌ها              (conditional)

اشتراک و آموزش
  ├── پلن‌های اشتراک        (moduleEnabled)
  └── دوره‌ها و کلاس‌ها    (moduleEnabled)

کسب‌وکار و نمایش
  ├── فاکتورها              (moduleEnabled)
  ├── حسابداری             (moduleEnabled)
  ├── آمار و تحلیل          (always)
  ├── مرکز اعلان‌ها          (always)
  ├── درگاه بانکی           (always)
  ├── کارت ویزیت دیجیتال   (moduleEnabled)
  ├── شبکه‌های اجتماعی     (moduleEnabled)
  └── تبلیغات              (moduleEnabled)

ظاهر، امنیت و تنظیمات
  ├── تنظیمات کپچا          (always)
  ├── ظاهر و تم             (always)
  ├── فروشگاه قالب‌ها       (platform_url set)
  ├── باندل‌های قالب         (platform_url set)
  ├── سلامت المان‌های سازنده (always)
  ├── ویدیوهای صفحه اصلی   (moduleEnabled)
  ├── ترجمه‌های هوش مصنوعی (moduleEnabled)
  ├── تنظیمات              (always)
  └── حذف ۴ صفر پول        (moduleEnabled)

لایسنس، ماژول‌ها و لاگ
  ├── لایسنس                (always)
  ├── ماژول‌ها              (always)
  ├── تاریخچه ماژول‌ها      (always)
  ├── فروشگاه ماژول         (platform_url set)
  └── لاگ فعالیت ادمین     (always)

آموزش و راهنما             ← ISOLATED GROUP for 1 item
  └── مرکز آموزش

دستیار محتوا               ← ISOLATED GROUP for 1 item
  └── دستیار محتوا

[DYNAMIC — appended per module if in license] ← POLLUTES SIDEBAR
  ├── سئو پیشرفته          ← has real page ✓
  ├── مخاطبین و CRM        ← has real page ✓
  ├── انبار و موجودی       ← has real page ✓
  ├── حسابداری            ← already in static group
  ├── تحلیل پیشرفته        ← ❌ DESCRIPTION ONLY
  ├── اعلان‌ها و اخبار     ← ❌ DESCRIPTION ONLY
  ├── پشتیبان‌گیری         ← ❌ DESCRIPTION ONLY
  ├── برند                 ← ❌ DESCRIPTION ONLY
  ├── ادغام ابر            ← ❌ DESCRIPTION ONLY
  ├── قرارداد              ← ❌ DESCRIPTION ONLY
  ├── تخفیف پیشرفته       ← ❌ DESCRIPTION ONLY
  ├── گالری                ← ❌ DESCRIPTION ONLY
  ├── زبان و چندگانه      ← ❌ DESCRIPTION ONLY
  ├── اتوماسیون بازاریابی  ← ❌ DESCRIPTION ONLY
  ├── همگام‌سازی مارکت    ← ❌ DESCRIPTION ONLY
  ├── چندارزی پویا         ← ❌ DESCRIPTION ONLY
  ├── پاسخ همه‌کاناله      ← ❌ DESCRIPTION ONLY
  ├── پادکست               ← ❌ DESCRIPTION ONLY
  ├── پورتفولیو            ← ❌ DESCRIPTION ONLY
  ├── بسته‌بندی محصول      ← ❌ DESCRIPTION ONLY
  ├── پروژه‌ها             ← ❌ DESCRIPTION ONLY
  ├── پرسش و پاسخ          ← ❌ DESCRIPTION ONLY
  ├── استخدام              ← ❌ DESCRIPTION ONLY
  ├── خدمات               ← ❌ DESCRIPTION ONLY
  ├── حمل و ارسال          ← ❌ DESCRIPTION ONLY
  ├── وظیفه و کار          ← ❌ DESCRIPTION ONLY
  ├── ویدیو و ولاگ         ← ❌ DESCRIPTION ONLY
  └── کیف پول              ← ❌ DESCRIPTION ONLY
```

**Problems:**
- Up to 24 description-only "coming soon" modules could appear as separate sidebar groups
- 2 isolated single-item groups (آموزش و راهنما, دستیار محتوا)
- `sms_campaigns` never visible (isVisible() bug)
- `crm`, `inventory`, `seo_advanced` float as isolated dynamic entries instead of being in meaningful groups

---

## AFTER (11 groups, clean, no description-only items)

```
داشبورد
  └── داشبورد

وبسایت
  ├── صفحات                (always)
  ├── منو و ناوبری         (moduleEnabled)
  ├── دستیار محتوا         (always)
  └── جستجوی پیشرفته      (moduleEnabled)

محتوا
  ├── دسته‌بندی‌های وبلاگ  (blogEnabled)
  ├── مجموعه‌های مطالب     (blogEnabled)
  ├── مطالب وبلاگ          (blogEnabled)
  ├── دوره‌ها و کلاس‌ها   (moduleEnabled)
  ├── ویدیوهای صفحه اصلی  (moduleEnabled)
  ├── ترجمه‌های AI         (moduleEnabled)
  └── مرکز آموزش           (always)     ← merged into محتوا

ظاهر
  ├── ظاهر و تم            (always)
  ├── فروشگاه قالب‌ها      (platform_url set)
  └── باندل‌های قالب        (platform_url set)

فروشگاه
  ├── دسته‌بندی محصولات    (shopEnabled)
  ├── محصولات              (shopEnabled)
  ├── ویژگی‌های فنی محصول (shopEnabled)
  ├── نظرات خریداران       (shopEnabled)
  ├── سفارشات              (shopEnabled)
  ├── کوپن‌ها              (moduleEnabled)
  ├── خرید قسطی           (moduleEnabled)
  └── انبار و موجودی      (moduleEnabled)  ← moved from dynamic

مارکتینگ
  ├── فرم‌ساز              (moduleEnabled)
  ├── گزارش ارسال فرم‌ها   (moduleEnabled)
  ├── لیست‌های دسترسی      (moduleEnabled)
  ├── معرفی دوستان         (moduleEnabled)
  ├── پاسخگویی شبکه‌ها    (moduleEnabled)
  ├── پیامک                (moduleEnabled)
  ├── کمپین‌های پیامکی     (moduleEnabled)  ← BUG FIXED
  ├── اعلان‌ها             (conditional)
  └── تبلیغات             (moduleEnabled)

کسب‌وکار
  ├── مخاطبین و CRM        (moduleEnabled)  ← moved from dynamic
  ├── نظرات و امتیازدهی   (moduleEnabled)
  ├── آمار و تحلیل         (always)
  ├── سئو پیشرفته          (moduleEnabled)  ← moved from dynamic
  ├── شبکه‌های اجتماعی    (moduleEnabled)
  └── کارت ویزیت دیجیتال  (moduleEnabled)

مالی
  ├── پلن‌های اشتراک       (moduleEnabled)
  ├── فاکتورها             (moduleEnabled)
  ├── حسابداری            (moduleEnabled)
  └── درگاه بانکی          (always)

تنظیمات
  ├── تنظیمات کپچا         (always)
  ├── تنظیمات             (always)
  └── حذف ۴ صفر پول       (moduleEnabled)

پشتیبانی
  ├── صندوق پشتیبانی       (always)
  └── پنل کاربران          (always)

سیستم
  ├── لایسنس               (always)
  ├── ماژول‌ها             (always)
  ├── تاریخچه ماژول‌ها     (always)
  ├── فروشگاه ماژول        (platform_url set)
  ├── لاگ فعالیت           (always)
  ├── مرکز اعلان‌ها         (always)
  └── سلامت سازنده         (always)

[DYNAMIC — only modules with REAL UI, not in static groups]
  (currently empty — all known real-UI modules are now in static groups)
```

---

## CHANGE SUMMARY

| Change | Type | Detail |
|--------|------|--------|
| 24 description-only modules blocked | REMOVE | MODULE_DESCRIPTION_ONLY filter in getDynamicPlatformMenuKeys() |
| `crm` moved to static group کسب‌وکار | IMPROVE | isVisible() checks moduleEnabled('crm') |
| `inventory` moved to static group فروشگاه | IMPROVE | isVisible() checks moduleEnabled('inventory') |
| `seo_advanced` moved to static group کسب‌وکار | IMPROVE | isVisible() checks moduleEnabled('seo_advanced') |
| `site_content` merged into وبسایت | RESTRUCTURE | Was isolated single-item group |
| `education_center` merged into محتوا | RESTRUCTURE | Was isolated single-item group |
| `sms_campaigns` visibility bug fixed | BUG FIX | Now shows when sms module enabled |
| 11 semantic groups replacing old structure | RESTRUCTURE | Matches user mental model |
