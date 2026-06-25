# HOSTING_EXPIRY_WIDGET_CERTIFICATION

> Date: 2026-06-25
> Task: CUSTOMER_HOSTING_EXPIRY_WIDGET_FIX
> Status: CUSTOMER_HOSTING_EXPIRY_FIXED

---

## Root Cause

`ExpiryReminderService::sendReminder()` stores the expiry date inside `PlatformSiteNotification.link`
as `__expiry__:host:YYYY-MM-DD`. When the platform admin renewed hosting (updated `sites.host_expires_at`),
this marker was **never updated** — the notification kept the old date indefinitely.
`SiteConfigController` read from this stale marker instead of the authoritative `Site.host_expires_at` field.

Secondary bug: remaining days were computed from the stale stored date, not from the actual field.
Post-renewal, the notification would continue to show the old expiry date and a "days remaining" count
that could be negative or wrong.

No CTA button existed for the tenant to request hosting renewal.

---

## Files Modified

### saas-platform

| File | Change |
|------|--------|
| `app/Http/Controllers/Api/SiteConfigController.php` | Always reads `Site.host_expires_at` as source of truth; deactivates notification if > 30 days; fixes stale link in DB; passes `/admin/inbox` + `تمدید هاست` as CTA |
| `app/Http/Controllers/Admin/SiteController.php` | Added `syncExpiryNotifications()` called after `$site->update()`; proactively deactivates or updates notifications when expiry dates change |

### customer-site

| File | Change |
|------|--------|
| `resources/views/admin/dashboard.blade.php` | CTA link rendered as amber button (not plain text); external links open in `_blank`, internal links open in `_self` |

---

## Source of Truth

`sites.host_expires_at` (platform DB) is the sole authoritative field for hosting expiry.
Remaining days are computed dynamically: `max(0, now()->diffInDays(host_expires_at, false))`.
Days are never stored or cached beyond the standard 5-minute `platform_site_config` TTL.

---

## Cache Findings

| Layer | Finding | Verdict |
|-------|---------|---------|
| `platform_site_config` Redis (300s) | Standard TTL; acceptable 5-min stale window | PASS |
| `PlatformSiteNotification.link` | Was stale post-renewal; now proactively synced | FIXED |
| Dashboard controller | No cache; always reads from PlatformUxService | PASS |

---

## Validation Evidence

### Per-Site Isolation
Each `PlatformSiteNotification` is scoped to `site_id`. `SiteConfigController` queries:
```php
PlatformSiteNotification::where('site_id', $siteId)->active()
```
where `$siteId` comes from license validation. No cross-site contamination is possible.

### Renewal Flow
1. Admin updates `sites.host_expires_at` to new_date in `SiteController::update()`
2. `syncExpiryNotifications()` runs:
   - If new_date > 30 days: `is_active = false` → notification hidden immediately
   - If new_date ≤ 30 days: `link` and `body` updated to new date
3. Next API call from customer-site (within 5 min): `SiteConfigController` returns updated data
4. Customer dashboard refresh: shows correct date

### Date After Renewal > 30 Days
- `SiteController::syncExpiryNotifications()` sets `is_active = false`
- `SiteConfigController` also checks: if `daysLeft > 30` → deactivates + skips
- Result: notification disappears from dashboard within 5 minutes

### Remaining Days Refresh
- Computed on every `/api/site-config` call: `max(0, diffInDays(host_expires_at, false))`
- Never stored; always current as of the API call time

### CTA Button
- `SiteConfigController` sets `link = '/admin/inbox'`, `link_text = 'تمدید هاست'`
- Dashboard renders as amber button (background: `bg-amber-600`, text: `text-white`)
- Internal links open in same tab; external links in new tab

---

## Final Verdict

**CUSTOMER_HOSTING_EXPIRY_FIXED**

All three reported issues resolved:
1. ✅ Date now belongs to the current site (enforced by site_id scoping)
2. ✅ After renewal, old dates no longer persist (proactive sync + reactive check)
3. ✅ Remaining days refresh dynamically on every API call
4. ✅ "تمدید هاست" CTA button added, linking to admin inbox
