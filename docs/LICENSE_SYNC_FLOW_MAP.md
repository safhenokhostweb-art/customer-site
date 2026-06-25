# LICENSE_SYNC_FLOW_MAP

**Phase 1 — Full Trace**
Generated: 2026-06-25

---

## Complete License Sync Workflow

```
Customer Admin Dashboard (any protected route)
         │
         ▼
  [EnsureLicenseValid Middleware]
  app/Http/Middleware/EnsureLicenseValid.php
         │
         ├─► isOwnerSite()? → skip (owner_site=true bypass)
         │
         ├─► route in PUBLIC_ROUTES? → skip (login, install, etc.)
         │
         ├─► License::current() — DB query (latest row)
         │     cached 300s via SetTenantContext (license.current key)
         │
         ├─► last_validated_at === null
         │   OR last_validated_at + revalidate_interval_minutes.isPast()?
         │         │  config('license.revalidate_interval_minutes', 5)
         │         │  env: LICENSE_REVALIDATE_MINUTES (default: 5 min)
         │         │
         │         YES ──► LicenseActivationService::syncWithPlatform()
         │         NO  ──► skip sync, check isValid() only
         │
         └─► license->isValid($host)? → redirect to license page if not
                 │
                 └─► Next::handle() — request proceeds normally
```

---

## syncWithPlatform() Internal Flow

```
LicenseActivationService::syncWithPlatform($license, $domain)
         │
         ▼
  validate($license->license_key, $domain)
         │
         ├─► config('site.platform_url') empty?
         │     → return [valid=false, error=config_missing]
         │
         ├─► Http::timeout(15)->acceptJson()
         │     [LICENSE_VERIFY_SSL=false] → withoutVerifying()
         │     POST {PLATFORM_URL}/api/license/validate
         │     body: {license_key, domain}
         │
         ├─► HTTP error (5xx, 4xx non-42x)?
         │     → return [valid=false, error=api_error, http_status=N]
         │
         ├─► Connection exception (timeout, SSL, DNS)?
         │     → return [valid=false, error=connection_error, http_status=0]
         │
         └─► HTTP 200: parse JSON
               ├─► valid=true  → return full data (modules, expires_at, etc.)
               └─► valid=false → return [error=revoked|expired|invalid_key|...]
         │
         ▼
  isDefinitePlatformRejection($result)?
     ['revoked','expired','invalid_key','domain_mismatch','site_inactive']
         │
         ├─► YES (definite rejection):
         │     license->update(platform_valid=false, modules=[], last_validated_at=now())
         │     → [valid=false, message=...]
         │     → EnsureLicenseValid redirects to license page
         │
         └─► NO (transient / network / server error):
               license->update(last_validated_at=now())  ← FIXED (was missing)
               Cache::put(license.transient_error.{id}, [...], TTL=2×interval)
               → [valid=true, sync_skipped=true]
               → banner shows in admin layout
               Banner clears on next successful sync:
                 Cache::forget(license.transient_error.{id})
```

---

## Platform-Side Endpoint

```
saas-platform/routes/api.php
  POST /api/license/validate
         │
         ▼
  LicenseController::validateLicense()
  app/Http/Controllers/Api/LicenseController.php
         │
         ▼
  LicenseServerService::validate($licenseKey, $domain)
  app/Services/LicenseServerService.php
         │
         ├─► Schema::hasTable('platform_licenses')? — guard
         ├─► findByKey() — HMAC-SHA256 lookup (lazy rehash from legacy SHA256)
         ├─► isRevoked()? → [valid=false, error=revoked]
         ├─► isExpired()? → [valid=false, error=expired]
         ├─► isDomainAllowed()? → [valid=false, error=domain_mismatch]
         ├─► site->is_active? → [valid=false, error=site_inactive]
         └─► → [valid=true, modules=[], expires_at, support_*]
```

---

## Warning Component (Banner)

```
customer-site/resources/views/admin/layout.blade.php
  line 251: @include('admin.partials.license-transient-banner')
         │
         ▼
  license-transient-banner.blade.php
         │
         ├─► License::current()
         ├─► Cache::get('license.transient_error.{id}')
         └─► if !empty($transientError) && is_array($transientError):
               show warning banner:
               "هشدار موقت لایسنس: آخرین تلاش هم‌گام‌سازی..."
               with HTTP status code and "تلاش مجدد" link
```

---

## Configuration Chain

| Setting | Source | Default |
|---------|--------|---------|
| PLATFORM_URL | `.env` → `config('site.platform_url')` | (empty) |
| LICENSE_REVALIDATE_MINUTES | `.env` → `config('license.revalidate_interval_minutes')` | 5 |
| LICENSE_VERIFY_SSL | `.env` → `config('license.verify_ssl')` | true |
| OWNER_SITE | `.env` → `config('site.owner_site')` | false |

---

## Cache Keys

| Key | TTL | Purpose |
|-----|-----|---------|
| `license.current` | 300s | Cached License model in SetTenantContext |
| `license.transient_error.{id}` | 2×interval min | Transient sync failure marker |
| `schema.has_table.licenses` | 3600s | Schema check cache |
