# HOSTING_WIDGET_FLOW_MAP

> Generated: 2026-06-25
> Task: CUSTOMER_HOSTING_EXPIRY_WIDGET_FIX

---

## Data Flow: "هشدار انقضای هاست" Widget

```
[PLATFORM SIDE — saas-platform]

  ExpiryReminderService::run()          (scheduler, daily)
    └─ shouldSend(site, 'host', host_expires_at)
        └─ if within 30 days AND no reminder in 10 days:
            └─ sendReminder(site, 'host', host_expires_at, 'هاست')
                ├─ Creates/updates PlatformSiteNotification
                │   site_id = site.id
                │   title   = 'هشدار انقضای هاست'
                │   link    = '__expiry__:host:YYYY-MM-DD'   ← stored date (was bug)
                │   body    = 'تاریخ انقضای هاست: {dateFa}. لطفاً برای تمدید اقدام کنید.'
                │   is_active = true, audience = admin, priority = high
                └─ Creates ExpiryReminderLog

  SiteController::update()              (admin renews site)
    └─ $site->update(['host_expires_at' => newDate])
        └─ syncExpiryNotifications(site)    ← NEW (fix)
            └─ finds PlatformSiteNotification where title='هشدار انقضای هاست'
                ├─ if new date > 30 days → is_active = false   (renewed)
                └─ if ≤ 30 days → updates link & body with new date

  GET /api/site-config                  (every 5 min from customer-site)
    └─ SiteConfigController::show()
        └─ PlatformSiteNotification::active()->where(site_id)
            └─ foreach notification:
                if link starts with '__expiry__:':
                  ├─ load Site.host_expires_at  ← NEW source of truth (fix)
                  ├─ if days_remaining > 30: deactivate + skip  ← NEW (fix)
                  ├─ rebuild body with actual days remaining
                  ├─ fix stale link in DB if date differs  ← NEW (fix)
                  └─ set link='/admin/inbox', link_text='تمدید هاست'  ← NEW CTA (fix)
        └─ returns: notifications[] array

[CUSTOMER SITE — customer-site]

  PlatformUxService::getSiteConfig()    (cached 300s in Redis: 'platform_site_config')
    └─ HTTP GET /api/site-config
        └─ cache::remember('platform_site_config', 300, fn)

  DashboardController::index()
    └─ PlatformUxService::getPlatformNotifications()
        └─ filter: type=warning/danger OR priority=high/urgent, audience=admin
            └─ $importantAlerts

  admin/dashboard.blade.php
    └─ @foreach($importantAlerts as $alert)
        └─ renders: title, body, CTA button  ← NEW button style (fix)
```

---

## Key Fields

| Field | Model | Purpose |
|-------|-------|---------|
| `sites.host_expires_at` | Site (platform) | **Source of truth** for hosting expiry |
| `sites.domain_expires_at` | Site (platform) | Source of truth for domain expiry |
| `sites.support_expires_at` | Site (platform) | Source of truth for support expiry |
| `platform_site_notifications.link` | PlatformSiteNotification | Stores `__expiry__:type:DATE` marker |
| `platform_site_notifications.is_active` | PlatformSiteNotification | Suppresses post-renewal notifications |

---

## Files Involved

### saas-platform
- `app/Services/ExpiryReminderService.php` — creates notifications, min 10-day interval
- `app/Http/Controllers/Api/SiteConfigController.php` — serves to customer-site (FIXED)
- `app/Http/Controllers/Admin/SiteController.php` — handles site updates (FIXED)
- `app/Models/PlatformSiteNotification.php` — notification model with `active()` scope

### customer-site
- `app/Services/PlatformUxService.php` — fetches & caches platform config
- `app/Http/Controllers/Admin/DashboardController.php` — passes `$importantAlerts`
- `resources/views/admin/dashboard.blade.php` — renders widget (FIXED)
- `resources/views/admin/partials/lifecycle-widget.blade.php` — lifecycle state widget (separate)
