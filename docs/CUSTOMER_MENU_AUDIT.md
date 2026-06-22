# CUSTOMER MENU AUDIT
> Generated: 2026-06-22
> Purpose: Classify every customer-side admin sidebar item for usefulness

---

## Audit Methodology

Each item evaluated against:
- Does it render a real page (forms / CRUD / analytics / settings / workflow)?
- Or does it render a description-only page (PlatformModuleController::show(), guide text, "coming soon" banner)?

---

## STATIC GROUP ITEMS (Always visible when condition met)

| Menu Item | Key | Route | Has Settings? | Has Actions? | Has CRUD? | Has Reports? | Useful? | Decision |
|-----------|-----|-------|---------------|--------------|-----------|--------------|---------|----------|
| داشبورد | dashboard | admin.dashboard | - | ✓ | - | ✓ | ✓ | **KEEP** |
| صفحات | pages | admin.pages.index | ✓ | ✓ | ✓ | - | ✓ | **KEEP** |
| منو و ناوبری | menu | admin.menus.index | ✓ | ✓ | ✓ | - | ✓ | **KEEP** |
| دسته‌بندی‌های وبلاگ | categories | admin.categories.index | - | ✓ | ✓ | - | ✓ | **KEEP** |
| مجموعه‌های مطالب | post_series | admin.post-series.index | - | ✓ | ✓ | - | ✓ | **KEEP** |
| مطالب وبلاگ | posts | admin.posts.index | - | ✓ | ✓ | - | ✓ | **KEEP** |
| جستجوی پیشرفته | advanced_search | admin.modules.show | ✓ | ✓ | - | - | ✓ | **KEEP** (has real settings form) |
| دسته‌بندی محصولات | product_categories | admin.product-categories.index | - | ✓ | ✓ | - | ✓ | **KEEP** |
| محصولات | products | admin.products.index | - | ✓ | ✓ | - | ✓ | **KEEP** |
| ویژگی‌های فنی محصول | product_attributes | admin.product-attributes.index | - | ✓ | ✓ | - | ✓ | **KEEP** |
| نظرات خریداران (فروشگاه) | shop_product_reviews | admin.shop-product-reviews.index | - | ✓ | ✓ | - | ✓ | **KEEP** |
| سفارشات | orders | admin.orders.index | - | ✓ | ✓ | ✓ | ✓ | **KEEP** |
| کوپن‌ها | coupon | admin.coupons.index | - | ✓ | ✓ | - | ✓ | **KEEP** |
| خرید قسطی | installments | admin.installments.plans.index | ✓ | ✓ | ✓ | ✓ | ✓ | **KEEP** |
| صندوق پشتیبانی | support | admin.inbox.index | - | ✓ | ✓ | ✓ | ✓ | **KEEP** |
| پنل کاربران | users_panel | admin.users.index | - | ✓ | ✓ | - | ✓ | **KEEP** |
| فرم‌ساز | forms | admin.forms.index | ✓ | ✓ | ✓ | - | ✓ | **KEEP** |
| گزارش ارسال فرم‌ها | forms_submissions | admin.forms.submissions.index | - | - | - | ✓ | ✓ | **KEEP** |
| لیست‌های دسترسی | access_lists | admin.access-lists.lists.index | ✓ | ✓ | ✓ | - | ✓ | **KEEP** |
| نظرات و امتیازدهی | reviews | admin.reviews.index | - | ✓ | ✓ | ✓ | ✓ | **KEEP** |
| معرفی دوستان | referral | admin.referrals.index | ✓ | ✓ | - | ✓ | ✓ | **KEEP** |
| پاسخگویی شبکه‌ها | whatsapp | admin.whatsapp.index | ✓ | ✓ | - | - | ✓ | **KEEP** |
| پیامک | sms | admin.sms.index | ✓ | ✓ | - | ✓ | ✓ | **KEEP** |
| کمپین‌های پیامکی | sms_campaigns | admin.sms.campaigns.index | ✓ | ✓ | ✓ | ✓ | ✓ | **KEEP** |
| اعلان‌ها | notifications | admin.notifications.index | ✓ | ✓ | ✓ | - | ✓ | **KEEP** |
| پلن‌های اشتراک | subscription | admin.subscriptions.index | ✓ | ✓ | ✓ | ✓ | ✓ | **KEEP** |
| دوره‌ها و کلاس‌ها | courses | admin.courses.index | ✓ | ✓ | ✓ | ✓ | ✓ | **KEEP** |
| فاکتورها | invoice | admin.invoices.index | - | ✓ | ✓ | ✓ | ✓ | **KEEP** |
| حسابداری | accounting | admin.accounting.index | - | - | - | ✓ | ✓ | **KEEP** (real KPIs + charts) |
| آمار و تحلیل | analytics_local | admin.analytics.index | - | - | - | ✓ | ✓ | **KEEP** |
| مرکز اعلان‌ها | system_alerts | admin.notification-center.index | - | ✓ | ✓ | - | ✓ | **KEEP** |
| درگاه بانکی | bank_gateway | admin.payment-gateways.index | ✓ | ✓ | ✓ | - | ✓ | **KEEP** |
| کارت ویزیت دیجیتال | business_card | admin.business-cards.index | - | ✓ | ✓ | - | ✓ | **KEEP** |
| شبکه‌های اجتماعی | social_links | admin.social-links.index | - | ✓ | ✓ | - | ✓ | **KEEP** |
| تبلیغات | advertisements | admin.advertisements.index | - | ✓ | ✓ | - | ✓ | **KEEP** |
| تنظیمات کپچا | captcha | admin.captcha.index | ✓ | ✓ | - | - | ✓ | **KEEP** |
| ظاهر و تم | theme_design | admin.theme-design.index | ✓ | ✓ | - | - | ✓ | **KEEP** |
| فروشگاه قالب‌ها | theme_marketplace | admin.theme-marketplace.index | - | ✓ | ✓ | - | ✓ | **KEEP** |
| باندل‌های قالب | theme_bundles | admin.theme-bundles.index | - | ✓ | ✓ | - | ✓ | **KEEP** |
| سلامت المان‌های سازنده | builder_health | admin.builder-health.index | - | - | - | ✓ | ✓ | **KEEP** (real status page) |
| ویدیوهای صفحه اصلی | homepage_videos | admin.homepage-videos.index | - | ✓ | ✓ | - | ✓ | **KEEP** |
| ترجمه‌های هوش مصنوعی | translations | admin.translations.index | - | ✓ | - | ✓ | ✓ | **KEEP** |
| تنظیمات | settings | admin.settings.index | ✓ | ✓ | - | - | ✓ | **KEEP** |
| حذف ۴ صفر پول | currency_reform | admin.currency-reform.index | ✓ | ✓ | - | - | ✓ | **KEEP** |
| لایسنس | license | admin.license.index | ✓ | ✓ | - | - | ✓ | **KEEP** |
| ماژول‌ها | modules | admin.modules.index | - | ✓ | - | - | ✓ | **KEEP** |
| تاریخچه ماژول‌ها | modules_changelog | admin.modules.changelog | - | - | - | ✓ | ✓ | **KEEP** |
| فروشگاه ماژول | module_store | admin.module-store.index | - | ✓ | ✓ | - | ✓ | **KEEP** |
| لاگ فعالیت ادمین | activity_log | admin.activity-log.index | - | - | - | ✓ | ✓ | **KEEP** |
| مرکز آموزش | education_center | admin.education-center.index | - | - | - | ✓ | ✓ | **KEEP** (searchable docs + learning paths) |
| دستیار محتوا | site_content | admin.site-content.index | ✓ | ✓ | ✓ | - | ✓ | **KEEP** |

---

## DYNAMIC PLATFORM MODULE ITEMS (license-based)

| Menu Item | Slug | Controller | Page Content | Useful? | Decision |
|-----------|------|------------|--------------|---------|----------|
| سئو پیشرفته | seo_advanced | SeoAdvancedController | Real SEO dashboard: KPIs, checklist, sitemap, quick links | ✓ | **KEEP** |
| مخاطبین و CRM | crm | CrmController | Full contacts CRUD, deals Kanban, activities | ✓ | **KEEP** |
| انبار و موجودی | inventory | InventoryController | Warehouses, items, movements, alerts | ✓ | **KEEP** |
| تحلیل پیشرفته | analytics | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** (analytics_local already covers this) |
| اعلان‌ها و اخبار | announcements | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| پشتیبان‌گیری | backup | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| برند | brand | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| ادغام ابر | cloud | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| قرارداد | contract | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| تخفیف پیشرفته | discount_advanced | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| گالری | gallery | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| زبان و چندگانه | i18n | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| اتوماسیون بازاریابی | marketing_automation | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| همگام‌سازی مارکت‌پلیس | marketplace_sync | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| چندارزی پویا | multi_currency | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| پاسخ همه‌کاناله | omnichannel | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| پادکست | podcast | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| پورتفولیو | portfolio | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| بسته‌بندی محصول | product_bundling | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| پروژه‌ها | projects | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| پرسش و پاسخ | qa | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| استخدام | recruitment | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| خدمات | services | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| حمل و ارسال | shipping | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| وظیفه و کار | tasks | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |
| ویدیو و ولاگ | video | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** (homepage_videos covers real video) |
| کیف پول | wallet | PlatformModuleController | ❌ Description-only "coming soon" page | ✗ | **REMOVE** |

---

## SUMMARY

| Category | Count |
|----------|-------|
| Static items — KEEP | 51 |
| Dynamic items — KEEP (moved to static groups) | 3 (seo_advanced, crm, inventory) |
| Dynamic items — REMOVE FROM SIDEBAR | 24 |

**Root cause:** `PlatformModuleController::show()` renders a "coming soon" banner + description for 24 platform modules that have no real admin UI yet. These modules appear in the sidebar when they're included in the license, creating menu pollution.

**Fix:** Filter these from `getDynamicPlatformMenuKeys()` and add the 3 real-UI platform modules to static groups with proper `isVisible()` checks.
