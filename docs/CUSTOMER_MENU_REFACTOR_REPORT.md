# CUSTOMER MENU REFACTOR REPORT
> Generated: 2026-06-22

---

## Problem Statement

The customer admin sidebar suffered from three distinct issues:

### 1. Description-Only Menu Pollution
When a license includes platform modules without a real admin UI, those modules appeared as standalone sidebar groups pointing to `PlatformModuleController::show()` — a page that renders:
- A yellow "coming soon" banner
- A 3-paragraph description of what the module does
- License activation instructions

24 modules fell into this category. A customer with a broad license could see 24 extra sidebar groups, each leading to a dead-end page.

### 2. Poor Group Semantics
The old grouping mixed unrelated items:
- "کسب‌وکار و نمایش" contained accounting, analytics, bank gateway, social links, AND business cards — 5 different domains
- CRM, inventory, and SEO had real pages but floated as isolated dynamic groups
- "مرکز آموزش" and "دستیار محتوا" were each isolated in their own group (single-item groups)

### 3. Bug: `sms_campaigns` Never Visible
`sms_campaigns` was added to `getStaticGroups()` but `isVisible('sms_campaigns')` had no handler — it fell through to `return false`. SMS campaigns were never shown in the sidebar despite having a complete admin UI.

---

## Changes Made

### File Modified
`customer-site/app/Helpers/AdminMenu.php`

### Change 1: `MODULE_DESCRIPTION_ONLY` constant (new)
```php
protected const MODULE_DESCRIPTION_ONLY = [
    'analytics', 'announcements', 'backup', 'brand', 'cloud', 'contract',
    'discount_advanced', 'gallery', 'i18n', 'marketing_automation', 'marketplace_sync',
    'multi_currency', 'omnichannel', 'podcast', 'portfolio', 'product_bundling',
    'projects', 'qa', 'recruitment', 'services', 'shipping', 'tasks', 'video', 'wallet',
];
```
Defines the 24 modules that render description-only pages.

### Change 2: `getDynamicPlatformMenuKeys()` filter
```php
if (in_array($slug, self::MODULE_DESCRIPTION_ONLY, true)) {
    continue;
}
```
Prevents description-only modules from appearing as sidebar items. They remain accessible via the Modules catalog (`/admin/modules`).

### Change 3: `MENU_KEYS` additions
Added 3 keys for platform modules now placed in static groups:
- `'crm' => 'مخاطبین و CRM'`
- `'inventory' => 'انبار و موجودی'`
- `'seo_advanced' => 'سئو پیشرفته'`

### Change 4: `getStaticGroups()` complete restructure
Old: 11 groups with poor semantics, 2 single-item groups, dynamic modules floating
New: 11 semantically-clear groups mapping to user mental models:

| Group | Persian | Purpose |
|-------|---------|---------|
| داشبورد | Dashboard | Central hub |
| وبسایت | Website | Pages, nav, static content |
| محتوا | Content | Blog, courses, video, docs |
| ظاهر | Appearance | Theme design and marketplace |
| فروشگاه | Commerce | Shop, orders, inventory |
| مارکتینگ | Marketing | Forms, campaigns, channels |
| کسب‌وکار | Business | CRM, analytics, SEO, social |
| مالی | Billing | Subscriptions, invoices, payments |
| تنظیمات | Settings | Captcha, config, currency |
| پشتیبانی | Support | Tickets, users |
| سیستم | System | License, modules, logs, health |

### Change 5: `isVisible()` additions
Three platform modules moved to static groups need explicit visibility rules:
```php
'crm'          => 'crm',
'inventory'    => 'inventory',
'seo_advanced' => 'seo_advanced',
```

### Change 6: `sms_campaigns` visibility bug fix
```php
'sms_campaigns' => 'sms', // visible when sms module enabled
```

---

## Invariants Preserved

- No routes modified
- No controllers modified
- No permissions changed
- No middleware changed
- Module pages remain accessible via `/admin/modules/{slug}`
- Description-only modules NOT deleted — only hidden from sidebar
- All existing module functionality unchanged

---

## What Gets Removed from Sidebar

All 24 entries below are removed from the sidebar. Their pages remain reachable at `/admin/{slug}` directly, and they appear in the modules catalog.

| Module | Reason for Removal |
|--------|-------------------|
| analytics | Duplicates analytics_local; description-only |
| announcements | Description-only "coming soon" |
| backup | Description-only |
| brand | Description-only |
| cloud | Description-only |
| contract | Description-only |
| discount_advanced | Description-only |
| gallery | Description-only |
| i18n | Description-only |
| marketing_automation | Description-only |
| marketplace_sync | Description-only |
| multi_currency | Description-only |
| omnichannel | Description-only |
| podcast | Description-only |
| portfolio | Description-only |
| product_bundling | Description-only |
| projects | Description-only |
| qa | Description-only |
| recruitment | Description-only |
| services | Description-only |
| shipping | Description-only |
| tasks | Description-only |
| video | Description-only; homepage_videos covers real video |
| wallet | Description-only |

---

## What Gets Promoted to Sidebar

Three platform modules previously shown as isolated dynamic groups are now placed in meaningful static groups:

| Module | Old Location | New Location | Why |
|--------|-------------|--------------|-----|
| seo_advanced | Dynamic (isolated) | کسب‌وکار | Has real SEO dashboard: KPIs, checklist, sitemap |
| crm | Dynamic (isolated) | کسب‌وکار | Has full contacts/deals management |
| inventory | Dynamic (isolated) | فروشگاه | Has warehouses/items/movements/alerts |

---

## Metrics

| Metric | Before | After |
|--------|--------|-------|
| Max sidebar groups (full license) | 11 + 24 = 35 | 11 |
| Single-item groups | 2 | 0 |
| Description-only items in sidebar | Up to 24 | 0 |
| Properly grouped platform modules | 0 | 3 |
| Visibility bugs fixed | 0 | 1 (sms_campaigns) |
