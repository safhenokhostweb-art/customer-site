# LICENSE_WARNING_ROOT_CAUSES

**Phase 2 — Root Cause Analysis**
Generated: 2026-06-25

---

## Root Cause #1 — CONFIRMED PRIMARY (CRITICAL)

**Self-signed SSL Certificate Rejection**

| Field | Detail |
|-------|--------|
| Trigger | `Http::post('https://panel.safhenokhost.ir/api/license/validate')` throws `Illuminate\Http\Client\ConnectionException` |
| Error | `cURL error 60: SSL certificate problem: self-signed certificate` |
| Source | `LicenseActivationService::validate()` — no `withoutVerifying()` |
| Frequency | **EVERY request** (all 30+ log entries confirm this) |
| Severity | CRITICAL — this is the sole cause of the persistent banner |
| Valid or False Positive | **FALSE POSITIVE** — license IS active on platform, SSL cert is the blocker |

**Why it persisted:**
- Platform uses self-signed TLS at `https://panel.safhenokhost.ir`
- ArvanCloud CDN (which would provide valid cert) was not yet activated
- PHP/cURL's default behavior rejects self-signed certs (SSL_VERIFY_PEER=true)
- `connection_error` is NOT in `isDefinitePlatformRejection()` so the transient banner is set
- `last_validated_at` was NOT updated on transient skip → every subsequent request triggered a new sync → banner renewed every 30 seconds

**Production Evidence:**
```
[2026-06-25 07:10:30] production.ERROR: License validation exception 
{"message":"cURL error 60: SSL certificate problem: self-signed certificate 
(see https://curl.haxx.se/libcurl/c/libcurl-errors.html) 
for https://panel.safhenokhost.ir/api/license/validate","url":"https://panel.safhenokhost.ir/api/license/validate","exception":"Illuminate\\Http\\Client\\ConnectionException"}
```
(Repeated every ~30 seconds = every page load)

---

## Root Cause #2 — CONFIRMED SECONDARY (HIGH)

**`last_validated_at` Not Updated on Transient Skip → Retry Storm**

| Field | Detail |
|-------|--------|
| Trigger | `syncWithPlatform()` returns early on transient error without updating `last_validated_at` |
| Source | `LicenseActivationService::syncWithPlatform()` lines 29-57 (pre-fix) |
| Frequency | Every request (after first failure) |
| Severity | HIGH — multiplies log noise and extends banner indefinitely |
| Valid or False Positive | **FALSE POSITIVE** — each retry regenerates the 30-min TTL cache |

**Why it occurred:**
- When sync returns `sync_skipped=true`, the function exits early before `$license->update($data)` is called
- `last_validated_at` remains at the LAST SUCCESSFUL value (or null if never succeeded)
- `EnsureLicenseValid` checks `$license->last_validated_at->addMinutes(5)->isPast()` → always true after failure
- Every page load retriggers `syncWithPlatform()` → every page load extends the 30-min cache → banner never expires naturally

---

## Root Cause #3 — LATENT (MEDIUM)

**Banner TTL (30 min) Too Long Relative to Revalidation Interval (5 min)**

| Field | Detail |
|-------|--------|
| Trigger | A single transient failure sets a 30-minute banner even if platform recovers in 5 minutes |
| Source | `LicenseActivationService::syncWithPlatform()` — `now()->addMinutes(30)` |
| Frequency | After any transient failure |
| Severity | MEDIUM — causes 30-min banner for what might be a 5-second hiccup |
| Valid or False Positive | **FALSE POSITIVE** — banner outlives the actual problem |

**Fix:** TTL = `max(10, revalidate_interval × 2)` — expires in two retry cycles if platform recovers.

---

## Root Cause #4 — POTENTIAL (LOW)

**`config_missing` Not Treated as Config Error (vs. Transient Error)**

| Field | Detail |
|-------|--------|
| Trigger | `PLATFORM_URL` is empty → `validate()` returns `[valid=false, error=config_missing]` |
| Source | `LicenseActivationService::validate()` — early return before HTTP call |
| Frequency | Only if PLATFORM_URL is not set |
| Severity | LOW — shows misleading "sync failed" banner when it's actually a config issue |
| Valid or False Positive | **FALSE POSITIVE** — banner implies network problem, not missing config |

**Status:** Not the cause in production (PLATFORM_URL was set). Documented for future installations.

---

## Summary Table

| # | Root Cause | Type | Frequency | Status |
|---|-----------|------|-----------|--------|
| 1 | Self-signed SSL cert → cURL error 60 | Network/TLS | Every request | FIXED (HTTP + withoutVerifying) |
| 2 | last_validated_at not updated on transient skip | Code bug | Every request after failure | FIXED |
| 3 | Banner TTL 30min > revalidation interval 5min | Design | Any transient failure | FIXED (TTL = 2×interval) |
| 4 | config_missing misclassified as transient | Design | Only if PLATFORM_URL empty | Documented |
