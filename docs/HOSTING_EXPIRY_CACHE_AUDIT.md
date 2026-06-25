# HOSTING_EXPIRY_CACHE_AUDIT

> Generated: 2026-06-25

---

## Cache Layers Affecting the Hosting Expiry Widget

### Layer 1 — `platform_site_config` (Redis, customer-site)

| Key | `platform_site_config` |
|-----|------------------------|
| Location | Customer-site Redis |
| Service | `PlatformUxService::CACHE_KEY` |
| TTL | 300 seconds (5 minutes) |
| Config key | `site.platform_config_cache_ttl` |
| Content | Full platform config: UX, notifications, modules, menus |
| Impact | After hosting renewal → stale for up to 5 min |
| Manual clear | `GET /admin/license/refresh-config` (triggers cache bust) |

**Verdict: ACCEPTABLE** — 5-minute stale window is standard for platform config delivery.

### Layer 2 — `PlatformSiteNotification` DB row (lazy source of truth sync)

| What | Notification `link` field contains `__expiry__:host:DATE` |
|------|----------------------------------------------------------|
| Was stale? | YES — only updated when ExpiryReminderService ran (every 10 days) |
| Now fixed? | YES — `SiteController::syncExpiryNotifications()` updates on every admin save |
| Fallback | `SiteConfigController` reads actual `Site.host_expires_at` on every API call |

**Verdict: FIXED** — Both proactive (SiteController) and reactive (SiteConfigController) sync.

### Layer 3 — No dashboard-level cache

`DashboardController::index()` does NOT cache. `importantAlerts` is always freshly computed from `PlatformUxService::getPlatformNotifications()` which reads from Redis Layer 1.

**Verdict: NO ISSUE**

### Layer 4 — `ExpiryReminderLog` cooldown (min 10-day gap)

`ExpiryReminderService::shouldSend()` throttles reminder creation to once per 10 days. This does NOT affect the notification display — only controls when new reminders are created.

**Verdict: NO ISSUE** — Notification display is independent of reminder creation frequency.

---

## Cache Risk Matrix

| Scenario | Cache Impact | Resolution |
|----------|-------------|------------|
| Renewal same day | 5-min stale on customer-site | Acceptable or clear via /admin/license/refresh-config |
| Renewal > 30 days ahead | Notification deactivated in DB immediately; customer sees it for up to 5 min then it disappears | Acceptable |
| New expiry set within 30 days | Notification updated in DB immediately; customer sees new date within 5 min | Acceptable |
| Platform API down | Uses cached config; widget shows stale data | Acceptable by design |

---

## Eliminated Cache Risks

- `__expiry__:host:DATE` in notification link was cached indefinitely in DB — now proactively synced
- No `Cache::remember()` calls anywhere in the expiry notification display path (platform side) 
- `Cache::flush()` is never called — isolated per named Redis store
