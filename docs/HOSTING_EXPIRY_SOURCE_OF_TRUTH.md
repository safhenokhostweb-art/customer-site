# HOSTING_EXPIRY_SOURCE_OF_TRUTH

> Generated: 2026-06-25

---

## Source of Truth: Per-Site Hosting Expiration

### Authoritative Fields

| Expiry Type | DB Table | Field | Model |
|-------------|----------|-------|-------|
| Hosting | `sites` | `host_expires_at` | `App\Models\Site` (saas-platform) |
| Domain | `sites` | `domain_expires_at` | `App\Models\Site` (saas-platform) |
| Support | `sites` | `support_expires_at` | `App\Models\Site` (saas-platform) |

### Site Isolation Guarantee

Each site has **exactly one row** in `sites` keyed by `id`. The `site_id` in `platform_site_notifications` always matches the validated license. There is no cross-site contamination in this flow.

### What Is NOT the Source of Truth

| NOT Source of Truth | Reason |
|--------------------|--------|
| `PlatformSiteNotification.link` field | Was stale after renewal — now always synced |
| `PlatformSiteNotification.body` field | Was stale after renewal — now always rebuilt |
| `PlatformUxService` Redis cache | Derived from API — 300s delay on update |
| Global server expiry | Not stored — each site has individual expiry |
| Platform-level `expires_at` | Not used for hosting expiry |

### How Source of Truth Is Enforced (Post-Fix)

```
1. SiteController::update() → syncExpiryNotifications()
   When admin saves host_expires_at:
   ├─ Finds PlatformSiteNotification for site+title
   ├─ If new date > 30 days: is_active = false  (suppressed)
   └─ If ≤ 30 days: link and body updated immediately

2. SiteConfigController::show() (every API call)
   For each __expiry__:type:DATE notification:
   ├─ Loads Site.host_expires_at (authoritative)
   ├─ Uses actual date (not stored date)
   ├─ If actual > 30 days: deactivates + skips notification
   └─ If stored date differs from actual: updates DB

3. ExpiryReminderService::sendReminder() (scheduler, daily)
   Creates/upserts notification with current host_expires_at
   (this is the initial write path — always uses live data)
```

### Remaining Days Calculation

Days remaining are NEVER stored. They are always computed:
```php
$daysLeft = max(0, (int) now()->diffInDays(Site::host_expires_at, false));
```

This runs at every `/api/site-config` request, ensuring the value is always current.
