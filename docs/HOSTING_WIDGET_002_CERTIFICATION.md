# HOSTING_WIDGET_002_CERTIFICATION

> Certifier: Claude Code
> Date: 2026-06-25
> Task: HOSTING-WIDGET-002 — Immediate Expiry Notification Creation

---

## Certification Checklist

| # | Check | Result |
|---|-------|--------|
| 1 | `syncExpiryNotifications()` creates record when none exists and `daysLeft ≤ 30` | PASS |
| 2 | `syncExpiryNotifications()` deactivates record when `daysLeft > 30` (renewal) | PASS |
| 3 | `syncExpiryNotifications()` deactivates record when date field is null | PASS |
| 4 | Creation payload matches `ExpiryReminderService::sendReminder()` field set | PASS |
| 5 | Scheduler's `sendReminder()` performs UPDATE (not duplicate INSERT) when record exists | PASS |
| 6 | No new tables or migrations introduced | PASS |
| 7 | No scheduler changes | PASS |
| 8 | `ExpiryReminderService::DAYS_WINDOW` used as constant (no magic number 30) | PASS |
| 9 | Null-safe operator (`?->`) used for optional deactivation | PASS |
| 10 | All three types covered: host / domain / support | PASS |

---

## Behavioral Change

| Scenario | Before Fix | After Fix |
|----------|-----------|-----------|
| Admin sets expiry within 30 days, no prior record | No warning (until 08:00 next day) | Warning appears within 5 min (Redis TTL) |
| Admin sets expiry within 30 days, record exists | Body/link updated immediately | Body/link updated immediately (unchanged) |
| Admin renews beyond 30 days, record exists | Record deactivated immediately | Record deactivated immediately (unchanged) |
| Admin clears expiry date, record exists | Record deactivated immediately | Record deactivated immediately (unchanged) |
| Scheduler runs next morning | Creates notification (late) | Updates existing notification — no duplicate |

---

## FINAL VERDICT

```
HOSTING_EXPIRY_IMMEDIATE_SYNC_OPERATIONAL
```

Notification Creation:  PASS
Notification Removal:   PASS
Duplicate Protection:   PASS

File Modified: `app/Http/Controllers/Admin/SiteController.php`
