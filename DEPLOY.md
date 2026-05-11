# Deployment Guide — customer-site

> Production-ready deployment scripts and rollback procedure for the **customer-site** (tenant-facing site) repository.

## 1. What gets deployed

The scripts execute these steps **in order** and stop immediately on any failure:

1. Create a timestamped rollback tag (`deploy-backup-YYYYMMDD-HHMMSS`) pointing at the current `HEAD` — your safety anchor.
2. `git fetch origin`
3. `git checkout main`
4. `git pull --ff-only origin main` (fast-forward only — refuses to clobber local commits)
5. `composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist`
6. `php artisan migrate --force` (runs only **new** migrations; never resets data)
7. `php artisan optimize:clear`
8. `php artisan optimize` (config + route + view caches)
9. *(Optional)* If `package-lock.json` exists **and** `node` + `npm` are available: `npm ci && npm run build`
10. Prints a reminder to restart queue workers when applicable.

The scripts **never** call `git reset`, `git clean`, `php artisan db:wipe`, or `migrate:fresh`. The active `licenses` row and customer module entitlements (driven by the license) are preserved.

## 2. Exact commands to run

### Linux / macOS

```bash
cd /path/to/customer-site
chmod +x deploy.sh           # first time only
./deploy.sh
```

### Windows Server (cmd.exe or PowerShell)

```bat
cd C:\path\to\customer-site
deploy.bat
```

On PowerShell, run `.\deploy.bat` (the leading `.\` is required).

## 3. Sample output

```
===============================================================
 customer-site deployment
 timestamp: 20260511-201500
===============================================================
current HEAD before deploy: ba4ee26 (ba4ee26b4d749dcc373e29c59f415b576e734156)

[..] Step 1: Create rollback tag deploy-backup-20260511-201500
[OK] Step 1: rollback tag created

[..] Step 2: git fetch origin
[OK] Step 2: git fetch origin

... (steps 3–8) ...

===============================================================
[OK] customer-site deploy completed successfully
 before: ba4ee26
 after:  abc1234
 backup tag (rollback): deploy-backup-20260511-201500
===============================================================
```

## 4. Rollback procedure

Every run creates a tag named `deploy-backup-YYYYMMDD-HHMMSS` pinned to the commit that was live just before the deployment. Find the tag with:

```bash
git tag --list 'deploy-backup-*'
```

### 4.a. Code-only rollback (fast)

If only application code is the problem (no migration introduced new schema):

```bash
git checkout main
git reset --hard deploy-backup-YYYYMMDD-HHMMSS
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
php artisan optimize:clear
php artisan optimize
```

### 4.b. Rollback including migrations (advanced)

If the failed deploy ran new migrations and you must back them out:

```bash
# 1) Inspect what was added since the backup tag
git log deploy-backup-YYYYMMDD-HHMMSS..HEAD -- database/migrations

# 2) For each new migration listed above, run:
php artisan migrate:rollback --step=1
# (repeat once per migration; review the rolled-back set after each step)

# 3) Then reset code
git checkout main
git reset --hard deploy-backup-YYYYMMDD-HHMMSS
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
php artisan optimize:clear
php artisan optimize
```

> Important: `migrate:rollback` may not be reversible for `DROP`/`ALTER` migrations. The Phase 5 / Wave 3 migration `2026_05_11_180000_create_admin_activity_log_table` **is** reversible (drops the audit log table on rollback). If you must keep the audit log, back up that table to SQL before rolling back.

### 4.c. Removing a bad rollback tag (housekeeping)

```bash
git tag -d deploy-backup-YYYYMMDD-HHMMSS         # local
git push origin :refs/tags/deploy-backup-YYY...  # remote (only if pushed)
```

By default the scripts **do not push** rollback tags. They stay local on the production server.

## 5. Recommended deployment workflow

```
   Cursor (local dev)
        │
        ▼
   Git Commit (feature branch + PR)
        │
        ▼
   GitHub (review, CI, merge into main)
        │
        ▼
   Staging customer-site  ──►  ./deploy.sh
        │                              │
        │                              ├── /admin/license — license still valid?
        │                              ├── /admin/activity-log — Wave 3 audit UI reachable?
        │                              ├── homepage renders for guest?
        │                              └── /admin/dashboard reachable for tenant admin?
        ▼
   Production customer-site  ──►  ./deploy.sh  (during maintenance window)
```

If staging deploy fails: investigate before touching production. Tag a fix, redeploy on staging, **then** production.

## 6. Compatibility

- **Laravel:** 12 (this repo) — scripts also compatible with 10/11 because they use only `migrate --force`, `optimize:clear`, `optimize`, all stable across these versions.
- **PHP:** 8.4+ (per `composer.json` constraint).
- **OS:** Linux (bash 4+) and Windows Server (cmd.exe / PowerShell).
- **Frontend:** Optional Vite build via `npm run build`. Skipped automatically if Node is not installed.
- **Queues:** If you run `php artisan queue:work` under Supervisor or as a Windows service, restart workers after each deploy:
  ```bash
  php artisan queue:restart            # signals workers to exit after current job
  sudo supervisorctl restart all       # only if Supervisor manages them
  ```

## 7. Safety guarantees (what the scripts will NEVER do)

The scripts honour the platform's global safety rules (`.cursor/rules/global-rules.mdc`):

- **No data destruction:** no `db:wipe`, no `migrate:fresh`, no `migrate:rollback`, no `git reset` to an older state, no `git clean`.
- **License & entitlements preserved:** `migrate --force` only runs **new** migrations; the active `licenses` row, `users`, `posts`, `products`, `orders`, and tenant data are untouched. The Phase 5 / Wave 2 lazy-encryption of `license_key` continues to work across deploys (existing rows stay readable; first save after deploy encrypts).
- **Customer module visibility preserved:** module enablement is driven by `License::current()->modules` plus the `whatsapp ↔ messaging` alias (P0-7). Deploy does not touch this state.
- **No force-push or destructive remote operation:** the only git operations are `fetch` and `pull --ff-only`. If `pull --ff-only` cannot fast-forward, the deploy aborts and asks the operator to investigate.
- **No silent failure:** every step prints `[OK]` on success or `[FAIL]` and exits non-zero on failure.

## 8. First-time setup on a new tenant host

1. Clone the repo:
   ```bash
   git clone https://github.com/safhenokhostweb-art/customer-site.git
   cd customer-site
   ```
2. Copy the production `.env` (out of band — never committed). Key entries:
   - `APP_KEY` (Laravel encryption key — **never rotate without the migration plan**, see §9)
   - `DB_*` (database credentials)
   - `PLATFORM_URL` (URL of saas-platform; used for license validation and module sync)
3. Install once:
   ```bash
   composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
   php artisan key:generate --force            # only if APP_KEY is empty
   php artisan storage:link
   php artisan migrate --force
   php artisan optimize
   ```
4. Configure the webserver (Apache/Nginx) to serve the `public/` directory.
5. Open `/install` in the browser to complete the first-time installer (creates the owner user and applies platform settings), **or** restore from a database backup if migrating an existing tenant.
6. In the admin panel, go to **لایسنس → فعال‌سازی** and enter the license key for this domain.
7. Subsequent deploys: `./deploy.sh` (Linux) or `deploy.bat` (Windows).

## 9. `APP_KEY` rotation — prohibited without migration plan

**Permanent policy (Phase 5 / Wave 2):** Do not rotate `APP_KEY` in production without:

1. Re-encrypting `licenses.license_key` for every customer site (loop: read with old key → write with new key).
2. Re-hashing `platform_licenses.license_key_hash` on saas-platform (impossible without each tenant re-presenting their plaintext key; you must coordinate a license re-issue).
3. Running the full Wave 2 validation runbook against the new key.
4. Parallel-key compatibility during the cutover.

If `APP_KEY` is lost or rotated incorrectly, every encrypted `license_key` becomes unreadable and **tenants will lose access**. The lazy-decrypt cast (`App\Casts\EncryptedOrPlaintext`) does **not** save you from a key change; it only smooths the one-way migration from legacy plaintext to encrypted under the same key.

## 10. Wave-tip tags (permanent names for development milestones)

In addition to automatic `deploy-backup-*` tags created by the deploy scripts, the repository carries **lightweight tags** that name the tip of each Phase 5 wave. These let you refer to important historical points without remembering raw SHAs.

| Tag | Commit | What it captures |
|---|---|---|
| `phase5-wave1-tip` | `85b263a` | End of Wave 1: security hardening, P0-3 (license-route allow-list), P0-7 (`whatsapp ↔ messaging` alias) |
| `phase5-wave2-tip` | `062bacb` | End of Wave 2: P0-4 (encrypted `license_key` with `EncryptedOrPlaintext` cast, lazy migration) |
| `phase5-wave3-tip` | `46e4fcc` | End of Wave 3: P1-2 (AdminActivityLog: migration + model + service + controller + UI + menu) |

These are **local-only** by design (not pushed to GitHub, to keep the remote tag list clean). They are stable, never advance, and survive deletion of the original `phase5-waveN` feature branches.

> ⚠️ **Tenant-specific caveat:** `git checkout phase5-waveN-tip` only changes the **code and migration files** on disk. The **database** stays at its current state. Never check out a wave-tip on a production server without coordinating a matching database backup/restore. Use it on a local copy for inspection only.

### Common operations

```bash
# List every wave-tip tag
git tag --list 'phase5-wave*-tip'

# Inspect repository state at the end of Wave 2 (read-only)
git checkout phase5-wave2-tip
# ... explore files (e.g. confirm EncryptedOrPlaintext cast is present) ...
git checkout main

# Compare two waves
git diff phase5-wave1-tip phase5-wave2-tip --stat

# List commits introduced by Wave 3
git log phase5-wave2-tip..phase5-wave3-tip --oneline

# Read a file as it was at a milestone
git show phase5-wave1-tip:app/Models/License.php

# Branch off a milestone for an emergency hotfix
git checkout -b hotfix-wave2 phase5-wave2-tip
```

### Future waves

After completing and merging each new wave, create the matching tag before deleting the feature branch:

```bash
# Replace N with the wave number, HASH with the tip commit (e.g. git rev-parse phase5-wave4)
git tag phase5-waveN-tip <HASH>
```

If you forgot to tag before merging, the wave tip is still recoverable from the merge commit's second parent: `git rev-parse <merge-commit>^2`.

### Publishing to GitHub (optional)

If you ever want the wave-tip tags on GitHub, push them explicitly (never `--tags` blindly — that would also push transient `deploy-backup-*` tags):

```bash
git push origin phase5-wave1-tip phase5-wave2-tip phase5-wave3-tip
```

For full operator details and Persian guidance, see `DEPLOY-fa.md` §10.

## 11. Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `pull --ff-only` fails | Local commits on production server | Inspect with `git log --oneline origin/main..HEAD`; do not rebase blindly. Stash or revert local edits, then redeploy. |
| `composer install` fails on a host with no network | Composer cannot reach packagist | Pre-populate `~/.composer/cache/` or use `composer install --no-scripts --no-plugins` offline. |
| `migrate --force` complains about missing table | First-ever deploy on this host | Run the first-time setup in §8 instead. |
| Admin panel shows "license invalid" right after deploy | License re-validation against saas-platform failed (network, expired support) | In **لایسنس → بروزرسانی ماژول‌ها**, click refresh. If the platform is unreachable, check `PLATFORM_URL` and the support window. |
| `/admin/activity-log` returns 500 right after deploy | Wave 3 migration not yet run | Re-run `php artisan migrate --force`; the `AdminActivityLogger` service guards with `Schema::hasTable` but the controller's index query requires the table. |
| Frontend assets stale | Browser cache | Hard-refresh; Vite output filenames are content-hashed so this should be rare. |
| Queue jobs run old code | Workers not restarted | `php artisan queue:restart` (and `supervisorctl restart all` if applicable). |

---

**Phase 5 / Wave 3 reference state:** `main` at `ba4ee26` (customer-site). Tags: `pre-phase5-baseline`, `phase5-wave1-tip` → `85b263a`, `phase5-wave2-tip` → `062bacb`, `phase5-wave3-tip` → `46e4fcc`.
