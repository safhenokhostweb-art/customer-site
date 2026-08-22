# Production Stability Fixes — Audit Report

Date: 2026-08-22
Scope: `customer-site` runtime, `saas-platform` license API contract, and read-only production infrastructure inspection.
Constraint: no commit, push, deployment, or production mutation was performed.

## Executive summary

1. Profile images failed because both profile controllers bypassed `MediaStorage` and wrote directly to `public/uploads`. Production directories are owned by `deployment-agent:deployment-agent` with mode `0755`, while PHP-FPM runs as `www-data`; the writes cannot succeed.
2. The valid-license warning came from `PLATFORM_URL=http://panel.safhenokhost.ir` plus the Bearer migration. The HTTP endpoint redirects to HTTPS and Authorization is stripped across that redirect. A production probe reproduced `401 missing_credentials` through HTTP, while direct HTTPS and localhost reached license validation normally.
3. Random ArvanCloud 504 responses correlate with saturation of the single PHP-FPM pool shared by the Laravel applications. It has only five children and repeatedly logs `server reached pm.max_children setting (5)`. Synchronous 15-second license validation amplified the saturation.

## Architecture involved

Profile uploads must flow through `SmartImageUploadService`, which resolves storage through `MediaStorage`; `ImageHelper`/`User::avatar_url` resolve persisted paths. This keeps local, S3, and Arvan disks replaceable.

`EnsureLicenseValid` periodically calls `LicenseActivationService::syncWithPlatform()` during an interactive request. The platform endpoint accepts Bearer authentication plus a legacy body fallback and rejects absent or conflicting credentials with 401. Only definite platform results (`expired`, `revoked`, `invalid_key`, `domain_mismatch`, `site_inactive`) may invalidate the cached projection.

ArvanCloud proxies to Nginx. All Laravel vhosts use `/run/php/php8.3-fpm.sock` and the same `www` pool. Nginx uses 120/300-second FastCGI read timeouts.

## Issue 1 — profile image upload

### Root cause and evidence

- The exact reported Persian message is emitted by `AdminProfileController` after direct `$file->move()` fails.
- `ProfileAvatarController` used the same direct-filesystem pattern and deleted the old image before the new write.
- Production state:
  - `public/uploads`: `drwxr-xr-x deployment-agent:deployment-agent`
  - `public/uploads/admin-avatars`: `drwxr-xr-x deployment-agent:deployment-agent`
  - PHP-FPM worker user: `www-data`
- The directories are not writable by PHP-FPM.
- Upload disks use `throw=false`; `SmartImageUploadService` previously ignored false returns from `put()`/`storeAs()`.

### Minimal fix

- Both controllers now use `SmartImageUploadService`/`MediaStorage`.
- The account flow uploads first, then deletes the prior image best-effort; failed storage preserves the existing image.
- False Flysystem writes now raise a controlled exception.
- Structured logs contain user id and exception class, not file contents.
- New paths are canonical (`avatar/YYYY/MM/...`); existing `uploads/admin-avatars/...` paths have a narrow compatibility branch.
- Admin views use `avatar_url` rather than constructing local URLs.

### Production correction required (not executed)

```bash
chown -R deployment-agent:www-data /var/www/customer-site/public/uploads
find /var/www/customer-site/public/uploads -type d -exec chmod 2775 {} +
find /var/www/customer-site/public/uploads -type f -exec chmod 0664 {} +
```

This is a runtime ownership correction, not a deployment-system change.

## Issue 2 — warning while license is valid

### Root cause and evidence

Production has `PLATFORM_URL=http://panel.safhenokhost.ir` and `LICENSE_VERIFY_SSL=false`.

| Production probe | Result |
|---|---|
| HTTP hostname, Bearer, redirect followed | 401 `missing_credentials` |
| Direct HTTPS, same non-secret Bearer probe | 200 `invalid_key` |
| Localhost with platform Host header | 200 `invalid_key` |

The key reaches Laravel over direct HTTPS/localhost but Authorization does not survive HTTP→HTTPS. The earlier strict-redirect fix preserved POST and solved 405, but did not preserve Authorization across a scheme change.

The sync service already retained `platform_valid=true` on 401. The UI error was that the transient banner rendered despite `License::isValid()` being true.

### Minimal fix

- Bearer remains canonical.
- The identical key is also sent in the platform-supported body fallback. Identical header/body values are accepted; conflicts remain 401.
- HTTP 401 is logged as authentication transport failure and never treated as a license rejection.
- The transient banner is suppressed for a valid cached license.
- Expired/revoked/invalid/domain-mismatch/inactive results still fail closed.
- Interactive validation is bounded to 2 seconds connect and 5 seconds total, both configurable.

Production must also set `PLATFORM_URL=https://panel.safhenokhost.ir` and rebuild config cache. Direct HTTPS removes the redirect and plaintext first hop.

## Issue 3 — random ArvanCloud 504

### Root cause and evidence

- PHP-FPM: `pm=dynamic`, `pm.max_children=5`, `start_servers=2`, `min_spare=1`, `max_spare=3`, `pm.max_requests=0`.
- Slowlog and slow-request tracing are disabled; request termination is unbounded.
- PHP-FPM recorded repeated saturation, including four `pm.max_children (5)` warnings from 01:17:42–01:18:57 on 2026-08-17 and events on 2026-08-11, 08-02, 07-29/31, and earlier dates.
- Retained Nginx logs contain zero origin-side 504s and zero upstream timeout/connect/premature-close events. Arvan therefore times out before Nginx's longer origin budget.
- Inspection did not show CPU, RAM, disk, OOM, or MySQL exhaustion: load `0.18/0.16/0.14`, 2.3 GiB available memory, root disk 72%, no OOM events, MySQL three threads and ~6.8 queries/s.
- A synchronous 15-second license call could occupy every worker and increase the shared queue delay.

### Minimal fixes

The code-side amplifier is fixed by the 2/5-second license network budget. During an authorized production window, apply capacity and slow-request observability (not executed):

```ini
; /etc/php/8.3/fpm/pool.d/www.conf
pm = dynamic
pm.max_children = 12
pm.start_servers = 3
pm.min_spare_servers = 2
pm.max_spare_servers = 4
pm.max_requests = 500
request_slowlog_timeout = 5s
slowlog = /var/log/php8.3-fpm-slow.log
```

Twelve children fit the inspected 3.8-GiB host based on current ~95–105 MiB worker RSS. This is a logged capacity correction, not a gateway-timeout increase. Validate with `php-fpm8.3 -tt`, reload FPM, and monitor RSS, saturation, and slowlog.

## Files changed

- `app/Http/Controllers/Admin/AdminProfileController.php`
- `app/Http/Controllers/Account/ProfileAvatarController.php`
- `app/Services/SmartImageUploadService.php`
- `app/Helpers/ImageHelper.php`
- `app/Services/LicenseActivationService.php`
- `config/license.php`
- `resources/views/admin/layout.blade.php`
- `resources/views/admin/profile/index.blade.php`
- `resources/views/admin/partials/license-transient-banner.blade.php`
- `tests/Feature/ProductionStability/AdminProfileImageUploadTest.php`
- `tests/Feature/ProductionStability/LicenseStabilityTest.php`
- `tests/Feature/Account/ProfileAvatarStorageRegressionTest.php`
- `docs/production-stability-fixes/AUDIT_REPORT.md`

No `saas-platform` application or deployment-system file changed. Its license endpoint already exposes the required compatible contract.

## Why previous work regressed

- The MediaStorage migration updated shared upload services but left both profile controllers on direct filesystem writes.
- A previous SSL workaround set `PLATFORM_URL` to HTTP. The later Bearer migration exposed cross-scheme header stripping. Strict redirects fixed the POST→GET regression but not credential stripping.
- Application count and background workload grew while the shared PHP-FPM pool remained at its default five children with no slowlog.

## Tests performed

- PHP lint passed for every changed PHP runtime/test file.
- Focused plus directly related regression suite: **34 tests, 102 assertions, all passed**.
- Coverage includes successful admin/customer upload, invalid image, storage failure, text persistence, existing-image preservation, legacy/new URL resolution, disk mapping, valid cache plus 5xx, timeout, DNS failure, HTTP 401, Bearer plus body transport, expired/revoked license, and prior banner/cache behavior.
- Read-only production checks covered ownership, safe environment values, HTTP/HTTPS/local license probes, Nginx config/log counts, PHP-FPM config/saturation, memory/load/disk/OOM, Supervisor, and MySQL status.

## Production verification

1. Apply upload ownership; test write/delete as `www-data`, upload a real profile image, then verify DB path and HTTP 200 image URL.
2. Set direct HTTPS `PLATFORM_URL`, rebuild config cache, refresh license, and verify no 401/banner for a valid license.
3. Apply and syntax-check the FPM settings, reload FPM, and verify workers plus health endpoints.
4. Under representative traffic, verify no new `pm.max_children` warnings, inspect >5-second slowlog entries, and confirm Arvan 504s cease.
5. Test an expired and revoked license after transport recovery to confirm fail-closed behavior.

## Remaining risks and status

- Source changes are local only. Production still has wrong upload ownership, HTTP `PLATFORM_URL`, and a five-child FPM pool.
- FPM sizing must be monitored under peak traffic; slowlog evidence may identify an additional slow endpoint.
- The task prohibited commit/push/deploy. ADR-019 requires Git and production synchronization plus health verification, so the task cannot be marked complete until an authorized deployment performs these corrections.
