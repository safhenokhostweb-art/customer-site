# CUSTOMER-SITE OFFSITE BACKUP AUDIT
> Date: 2026-06-25
> Status: AUDIT_COMPLETE — 3 DEFECTS FOUND
> Scope: customer-site backup infrastructure vs Arvan integration

---

## Audit Summary

| Check | Result | Detail |
|-------|--------|--------|
| `BackupDatabaseCommand` exists | ✅ PASS | `app/Console/Commands/BackupDatabaseCommand.php` |
| `db:backup` scheduled | ✅ PASS | `bootstrap/app.php` line 69 — daily 02:00 |
| `arvan_backup` disk configured | ✅ PASS | `config/filesystems.php` — S3 driver, `env(ARVAN_*)` |
| `league/flysystem-aws-s3-v3` in composer.json | ❌ FAIL | Missing — S3 driver cannot load |
| Arvan upload in `BackupDatabaseCommand` | ❌ FAIL | No upload code — local only |
| ARVAN_* vars in server .env | ❌ FAIL | Grep returned NOT FOUND on server |
| `storage:backup` command | ⚪ N/A | Not implemented by design (per task: document, don't create) |

**Defect count: 3 (all blocking)**

---

## Component Inventory

### 1 — BackupDatabaseCommand
**File:** `app/Console/Commands/BackupDatabaseCommand.php`
**Signature:** `php artisan db:backup {--retention=7} {--no-compress} {--dry-run}`
**Local backup path:** `storage/backups/backup_{Y-m-d_H-i-s}.sql.gz`
**Arvan upload:** ❌ MISSING — no upload code anywhere in the file
**Integrity check:** ✅ EXISTS — row count before/after, size > 100 bytes
**Retention:** ✅ EXISTS — `applyRetention()` prunes old files

### 2 — Scheduler Registration
**File:** `bootstrap/app.php` (Laravel 11 style — no Kernel.php)
**Schedule:** `db:backup --retention=7` daily at 02:00
**Log:** `storage/logs/db-backup.log`
**onFailure:** ✅ `Log::error(...)` registered

### 3 — arvan_backup Filesystem Disk
**File:** `config/filesystems.php` lines 72-84
**Driver:** s3
**Key:** `env('ARVAN_ACCESS_KEY')` ✅ env-based
**Secret:** `env('ARVAN_SECRET_KEY')` ✅ env-based
**Region:** `env('ARVAN_REGION', 'ir-thr-at1')` ✅
**Bucket:** `env('ARVAN_BUCKET', 'safhenokhost-backups')` ✅
**Endpoint:** `env('ARVAN_ENDPOINT')` ✅ env-based
**use_path_style_endpoint:** `true` ✅
**visibility:** `private` ✅
**Status:** Config is correct — but disk is never used

### 4 — ArvanBackupService
**File:** DOES NOT EXIST in customer-site
**Decision:** Inline upload in `BackupDatabaseCommand` using `Storage::disk('arvan_backup')` directly
  — this avoids creating a new module while still fixing the defect

### 5 — PLATFORM_SITE_ID
**Source:** `.env` — `PLATFORM_SITE_ID=2`
**Purpose:** Tenant isolation in Arvan bucket
**Expected Arvan path:** `customer-sites/tenant-2/database/{filename}`

### 6 — Storage Backup
**Status:** NOT IMPLEMENTED — by design
**Reason:** customer-site's `storage/app/public/` is already covered by saas-platform's
`storage:backup` which backs up the platform storage. Customer-site media files are typically
served from the same VPS partition. A separate `storage:backup` would be a NEW feature
and is outside launch freeze scope. Documented as post-launch item.

---

## Defects Found

### DEFECT-1: Arvan upload missing from BackupDatabaseCommand (CRITICAL)
**Evidence:** `BackupDatabaseCommand.php` lines 1-205 — no `Storage::disk('arvan_backup')` call
**Impact:** Customer-site database backups are LOCAL ONLY. VPS failure = total data loss.
**Fix:** Add upload block after line 91 (successful backup), using `Storage::disk('arvan_backup')`
**Tenant path:** `customer-sites/tenant-{PLATFORM_SITE_ID}/database/{filename}`

### DEFECT-2: league/flysystem-aws-s3-v3 missing (CRITICAL)
**Evidence:** `grep flysystem-aws c:/laragon/www/customer-site/composer.json` → empty
**Impact:** Even after adding upload code, `Storage::disk('arvan_backup')->put()` would throw
  `Class League\Flysystem\AwsS3V3\PortableVisibilityConverter not found`
**Fix:** Add `"league/flysystem-aws-s3-v3": "^3.0"` to `composer.json` require section

### DEFECT-3: ARVAN_* env vars missing from server .env (CRITICAL)
**Evidence:** SSH check on `/var/www/customer-site/.env` → `NOT FOUND`
**Impact:** Even after code fix, disk would have null credentials → silent upload failure
**Fix:** Append ARVAN vars to customer-site server .env via SSH (same values as saas-platform)
  Note: ARVAN_ENDPOINT must be base URL `https://s3.ir-thr-at1.arvanstorage.ir` (not bucket-specific)

---

## Tenant Isolation Evidence

The Arvan path will be `customer-sites/tenant-{PLATFORM_SITE_ID}/database/{filename}`.

With `PLATFORM_SITE_ID=2`, all backups for this tenant go to:
```
safhenokhost-backups/
  customer-sites/
    tenant-2/
      database/
        backup_2026-06-25_*.sql.gz
```

Each customer-site has a unique `PLATFORM_SITE_ID` in its `.env`. Future tenants on different
VPS instances would use their respective IDs (e.g., tenant-3, tenant-4), keeping backups isolated.
No two tenants can write to the same Arvan path.

---

*CUSTOMER_SITE_BACKUP_AUDIT — generated 2026-06-25*
