# CUSTOMER_SITE_OFFSITE_BACKUP_CERTIFICATION

**Date:** 2026-06-25  
**Auditor:** Claude Code (Sonnet 4.6)  
**Task:** CUSTOMER_SITE_OFFSITE_BACKUP_FINAL_VERIFICATION  
**Status:** CERTIFIED ✓

---

## Certification Summary

Customer-site offsite backup infrastructure has been fully verified and all three identified defects have been resolved. Database backups now upload to ArvanCloud object storage using tenant-isolated paths.

---

## Defects Found and Fixed

| # | Defect | Severity | Fix | Status |
|---|--------|----------|-----|--------|
| D-01 | No Arvan upload code in `BackupDatabaseCommand.php` | CRITICAL | Added `uploadToArvan()` with 3×retry, size verification, tenant-isolated path | FIXED |
| D-02 | `league/flysystem-aws-s3-v3` missing from `composer.json` | CRITICAL | Added `"league/flysystem-aws-s3-v3": "^3.0"` | FIXED |
| D-03 | ARVAN_* env vars missing from server `.env` | CRITICAL | Added via Python script (BOM-safe) | FIXED |
| D-04 | `env('PLATFORM_SITE_ID')` returns null when config is cached | HIGH | Changed to `config('site.platform_site_id')` | FIXED |

---

## Architecture

```
customer-site (production: 87.107.12.81)
│
├── BackupDatabaseCommand (php artisan db:backup)
│   ├── Scheduled: daily at 02:00 UTC (bootstrap/app.php)
│   ├── Local backup: storage/backups/backup_{timestamp}.sql.gz
│   ├── Retention: 7 copies
│   └── Arvan upload: uploadToArvan() → 3× retry + size verify
│
└── ArvanCloud Object Storage
    └── Bucket: safhenokhost-backups (ir-thr-at1)
        └── Path: customer-sites/tenant-{PLATFORM_SITE_ID}/database/{filename}
```

### Tenant Isolation

- `PLATFORM_SITE_ID=1` (server `.env`)
- `config('site.platform_site_id')` → reads from cached config correctly
- Remote path: `customer-sites/tenant-1/database/backup_{timestamp}.sql.gz`
- Completely isolated from platform backups at `saas-platform/database/`

---

## Upload Method

```php
// BackupDatabaseCommand::uploadToArvan()
$siteId     = (int) config('site.platform_site_id', 0);   // uses config(), not env()
$remotePath = "customer-sites/tenant-{$siteId}/database/{$filename}";
$maxRetries = 3;

// per-attempt:
Storage::disk('arvan_backup')->put($remotePath, $stream);
Storage::disk('arvan_backup')->exists($remotePath);        // verify existence
Storage::disk('arvan_backup')->size($remotePath);          // verify size match
```

### Non-blocking design

Upload failure:
- Does NOT fail the backup command (exit code stays SUCCESS)
- Logs `Log::error('db:backup arvan failed', ...)` for alerting
- Does NOT delete the local backup

---

## Infrastructure Config

| Item | Value |
|------|-------|
| S3 driver | `league/flysystem-aws-s3-v3 ^3.0` |
| Bucket | `safhenokhost-backups` |
| Region | `ir-thr-at1` |
| Endpoint | `https://s3.ir-thr-at1.arvanstorage.ir` (path-style base) |
| Path style | `use_path_style_endpoint = true` |
| Visibility | `private` |
| Config key | `filesystems.disks.arvan_backup` |
| Site ID config | `config('site.platform_site_id')` |

---

## Integration with saas-platform

| Dimension | saas-platform | customer-site |
|-----------|--------------|---------------|
| Command | `php artisan db:backup` | `php artisan db:backup` |
| Schedule | daily 02:00 | daily 02:00 |
| Service | `ArvanBackupService` (with circuit) | inline `uploadToArvan()` (reuse not possible — no package dependency) |
| Remote path | `saas-platform/database/{file}` | `customer-sites/tenant-{id}/database/{file}` |
| Isolation | platform namespace | per-tenant namespace |

---

## Verification Evidence

```
# Live run on production server — 2026-06-25 12:13 Tehran time

$ php artisan config:clear && php artisan config:cache && php artisan db:backup

   INFO  Configuration cache cleared successfully.
   INFO  Configuration cached successfully.

شروع پشتیبان‌گیری: backup_2026-06-25_12-13-03.sql.gz
فایل ایجاد شد: 75.8 KB
Integrity OK — 951 سطر.
1 فایل قدیمی حذف شد.
پشتیبان‌گیری با موفقیت انجام شد.
Arvan offsite backup: آپلود به customer-sites/tenant-1/database/backup_2026-06-25_12-13-03.sql.gz (77582 bytes)

# UPLOAD CONFIRMED — size: 77582 bytes
# Tenant isolation: customer-sites/tenant-1/database/ ✓
# config() used (not env()) — works with config:cache ✓
```

---

## Scheduled Verification

Next automatic backup runs at **02:00 UTC (2026-06-26)**.

Expected log entry:
```
[2026-06-26 02:00:xx] production.INFO: db:backup arvan success {
  "filename": "backup_2026-06-26_06-30-xx.sql.gz",
  "remote_path": "customer-sites/tenant-1/database/backup_2026-06-26_06-30-xx.sql.gz",
  "size": ...,
  "attempt": 1
}
```

---

## Final Verdict

**CUSTOMER_SITE_OFFSITE_BACKUP_CERTIFIED**

All 4 defects resolved. Tenant-isolated ArvanCloud upload is wired to the daily scheduled backup. Platform-level backup (saas-platform) is already operational and certified separately.
