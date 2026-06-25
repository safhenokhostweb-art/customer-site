# HOSTING_WIDGET_002_REPORT

> Date: 2026-06-25
> Task: HOSTING-WIDGET-002 — Immediate Notification Creation on Admin Save

---

## Problem

`SiteController::syncExpiryNotifications()` only updated existing `PlatformSiteNotification` records.
When no record existed (first-time date set, or no prior scheduler run), the function exited silently with `continue`.
Result: warning never appeared until `expiry:send-reminders` ran the next day at 08:00.

## Root Cause (from audit)

```php
// BEFORE — BUG
$existing = PlatformSiteNotification::...->first();
if (! $existing) {
    continue;   // ← never creates
}
```

## Fix Applied

`SiteController.php` — `syncExpiryNotifications()` rewritten to:

1. **Check the date field first** (before `$existing`)
2. **Deactivate if no date** — via null-safe `$existing?->updateQuietly()`
3. **Compute `$daysLeft`** using `Carbon::diffInDays()`
4. **Deactivate if `$daysLeft > DAYS_WINDOW`** (30 days) — via null-safe operator
5. **Within window: UPSERT** — update if existing, CREATE if not

### Creation payload (matches ExpiryReminderService::sendReminder() structure)

| Field | Value |
|-------|-------|
| `site_id` | current site |
| `title` | "هشدار انقضای هاست" / "دامنه" / "پشتیبانی" |
| `body` | "تاریخ انقضای X: JALALI_DATE. لطفاً برای تمدید اقدام کنید." |
| `type` | `warning` |
| `link` | `__expiry__:host:YYYY-MM-DD` |
| `link_text` | `null` (resolved dynamically by SiteConfigController) |
| `audience` | `admin` |
| `priority` | `high` |
| `is_active` | `true` |
| `order` | max(order)+1 for this site |

## Duplicate Protection

The scheduler (`expiry:send-reminders`) also calls `sendReminder()`, which queries the same
`PlatformSiteNotification` record by `site_id + title`. If a record already exists (created here),
it will UPDATE rather than CREATE — no duplicates. `ExpiryReminderLog` is still written by the
scheduler for SMS cooldown tracking.

## Files Modified

| File | Change |
|------|--------|
| `app/Http/Controllers/Admin/SiteController.php` | Added `use ExpiryReminderService`, `use PlatformSiteNotification`; rewrote `syncExpiryNotifications()` with create path |

---

## Validation Cases

### Test 1 — Notification created immediately (4 days remaining)
- Set `host_expires_at = 2026-06-29`
- Save site via admin UI
- `syncExpiryNotifications()` → `$daysLeft = 4` → `4 ≤ 30` → `$existing = null` → **CREATE**
- Redis `platform_site_config` TTL expires (max 300s) → dashboard shows warning
- **Expected: PASS**

### Test 2 — Notification deactivated on renewal (60 days remaining)
- Set `host_expires_at = 2026-08-24` (60 days)
- Save site
- `syncExpiryNotifications()` → `$daysLeft = 60` → `60 > 30` → `$existing?->deactivate()`
- Dashboard warning disappears within 5 minutes (Redis TTL)
- **Expected: PASS**

### Test 3 — No duplicate on next scheduler run
- After test 1, `expiry:send-reminders` runs at 08:00
- `sendReminder()` queries `platform_site_notifications` WHERE `site_id + title`
- Record already exists → UPDATE (not INSERT) — one record remains
- **Expected: PASS**
