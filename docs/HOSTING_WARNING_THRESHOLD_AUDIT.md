# HOSTING_WARNING_THRESHOLD_AUDIT

> Date: 2026-06-25
> Trigger: host_expires_at set to 1405/04/08, no warning visible on dashboard

---

## Date Calculation

| Input | Value |
|-------|-------|
| Jalali input | 1405/04/08 |
| Converted Gregorian | 2026-06-29 |
| Today | 2026-06-25 |
| Days remaining | **4 days** |
| Warning threshold (`DAYS_WINDOW`) | **30 days** |
| Is 4 ≤ 30? | **YES** |

Days remaining (4) is well inside the warning threshold (30).
**The date itself is not the problem.**

---

## Exact Warning-Generation Conditions

Warning appearance requires TWO independent conditions to BOTH be true:

### Condition 1 — A `PlatformSiteNotification` record must exist
```
platform_site_notifications
  WHERE site_id = {this site}
  AND   title   = 'هشدار انقضای هاست'
  AND   is_active = 1
  AND   (starts_at IS NULL OR starts_at <= NOW())
  AND   (expires_at IS NULL OR expires_at >= NOW())
```
If this query returns zero rows → **no warning, ever.**

### Condition 2 — `SiteConfigController` must NOT skip it
```php
if ($daysLeft > ExpiryReminderService::DAYS_WINDOW) {  // > 30
    $n->updateQuietly(['is_active' => false]);
    continue;  // skipped
}
```
With 4 days remaining, this check passes (4 ≤ 30) → **not the cause.**

---

## Which Field Is Used

| Step | Field/Source |
|------|-------------|
| Notification creation | `sites.host_expires_at` via `ExpiryReminderService` |
| Notification display | `sites.host_expires_at` via `SiteConfigController` (source-of-truth lookup after yesterday's fix) |
| Dashboard rendering | `$importantAlerts` from `PlatformUxService::getPlatformNotifications()` |

All three use `sites.host_expires_at`. The field is unified. ✓

---

## The Gap: Who Creates the Notification?

There are **two paths** for `PlatformSiteNotification`:

### Path A — `ExpiryReminderService::run()` (CREATION + UPDATE)
- Triggered by: `php artisan expiry:send-reminders`
- Scheduler: **daily at 08:00** (`Kernel.php` line 15)
- Creates OR updates the notification record
- `shouldSend()` guard:
  ```
  if expires_at is past              → skip
  if daysLeft > 30                   → skip
  if last_log exists AND < 10 days   → skip (MIN_DAYS_BETWEEN_REMINDERS)
  else                               → CREATE or UPDATE notification ✓
  ```

### Path B — `SiteController::syncExpiryNotifications()` (UPDATE ONLY)
- Triggered by: admin saving a site via the UI
- Code:
  ```php
  $existing = PlatformSiteNotification::where(...)->first();
  if (! $existing) {
      continue;  ← EXITS WITHOUT CREATING
  }
  ```
- **Cannot create records. Only updates or deactivates existing records.**

---

## Root Cause of Missing Warning

`syncExpiryNotifications()` contains this code at line 328-329:

```php
if (! $existing) {
    continue;   // ← BUG: no creation path
}
```

When the admin sets `host_expires_at = 1405/04/08` via the site edit form:
1. `SiteController::update()` saves the date to `sites.host_expires_at` ✓
2. `syncExpiryNotifications()` is called ✓
3. It queries `PlatformSiteNotification` for title "هشدار انقضای هاست" + site_id
4. **If no record found → `continue` → function exits → no notification created**
5. Dashboard queries `/api/site-config` → notifications array is empty → no warning

The notification WILL be created when `expiry:send-reminders` next runs (daily 08:00).
If the date was set AFTER today's 08:00 run → notification appears **tomorrow at 08:00** at the earliest.

---

## Summary Table

| Check | Expected | Actual | Status |
|-------|----------|--------|--------|
| `host_expires_at` stored correctly | 2026-06-29 | Stored as 2026-06-29 | ✓ PASS |
| Days remaining | 4 | 4 | ✓ PASS |
| Within DAYS_WINDOW (30) | YES | YES | ✓ PASS |
| PlatformSiteNotification record exists | REQUIRED | **Missing** | ✗ FAIL |
| syncExpiryNotifications() creates record | Expected | **Does not create** | ✗ GAP |
| expiry:send-reminders scheduled | Daily 08:00 | Confirmed | ✓ but not run yet today |

---

## Final Verdict

**WARNING_VISIBLE: NO**

**ROOT_CAUSE:** `syncExpiryNotifications()` (called on every `SiteController::update()`) is a sync-only function — it only updates or deactivates existing `PlatformSiteNotification` records. It never creates new records. No notification record exists for this site. The creation path is exclusively `ExpiryReminderService::run()` → `expiry:send-reminders` command, which runs daily at 08:00. The warning will appear automatically tomorrow morning at 08:00 when the scheduler runs, provided no `ExpiryReminderLog` for type 'host' exists with `sent_at` within the last 10 days.

**Immediate workaround (no code change):** Run `php artisan expiry:send-reminders` manually on the saas-platform server to trigger notification creation immediately.

**Fix required in code:** `syncExpiryNotifications()` must CREATE a new notification when `$existing === null` AND `$daysLeft <= DAYS_WINDOW`. (Out of scope for this audit — see separate fix task.)
