# Customer-Site Runtime Contract

Scope: `customer-site` filesystem behavior under immutable-Artifact deployment.
Companion to [`docs/RELEASE_GATE.md`](../RELEASE_GATE.md) (Artifact completeness)
and [`docs/production-stability-fixes/`](../production-stability-fixes/) (prior
profile-upload/license/FPM hardening). This document is the persistence half
of the story: which paths must survive a release swap, and why read-only
rendering must never depend on writable directories existing.

## Incident this contract closes

`public/uploads` (the `uploads` disk root, `public_path('uploads')`) was
absent from an activated release. `www-data` (PHP-FPM) cannot write into
`public/` (owned `deployment-agent:deployment-agent`). Rendering a homepage
video thumbnail called `ImageHelper::url()` → `MediaStorage::url()` →
`Storage::disk('uploads')`, which resolves a
`League\Flysystem\Local\LocalFilesystemAdapter`. That adapter's constructor
eagerly runs `mkdir()` on its root unless told not to
(`vendor/league/flysystem-local/LocalFilesystemAdapter.php`, `$lazyRootCreation`
defaults to `false`; Laravel's `FilesystemManager::createLocalDriver()` never
passes it). `mkdir` failed, Flysystem threw `UnableToCreateDirectory`, and a
plain page render became a fatal 500 — before any read or write operation
actually ran.

This is two distinct, previously-conflated failure classes:

- **Persistence** — `public/uploads` holds real user media and cannot be
  treated as disposable release content (this incident).
- **Completeness** — a prior incident where `vendor/autoload.php` was
  missing from an activated release (already closed by
  `scripts/validate-release.php`, see [§ Artifact completeness](#artifact-completeness) below).

Fixing today's exception is not enough: the same eager-mkdir behavior sits
behind every local Flysystem disk resolution, so the underlying bug had to
be fixed at the `MediaStorage` layer, not patched at the one call site that
happened to crash first.

## Immutable Release Content

Everything below is git-tracked, produced by the build (`composer install`,
`npm run build`), and shipped as part of the Artifact. Replaced wholesale on
every release; never mutated in place; never contains persistent user data.

| Path | Reader/Writer | Notes |
|---|---|---|
| `app/`, `bootstrap/`, `config/`, `database/migrations`, `resources/`, `routes/` | PHP-FPM reads | Source code |
| `vendor/` | PHP-FPM reads | Composer output; production never runs `composer install` |
| `vendor/saas-platform/*` internal packages | PHP-FPM reads | Path-repository packages, must be physically inside the release tree (junction/symlink to the dev machine is rejected by `validate-release.php`'s `internal_packages` check) |
| `public/build/manifest.json`, `public/build/assets/` | PHP-FPM/web server read | Vite output, pre-built and shipped |
| `artisan`, `composer.json`, `composer.lock`, `public/index.php` | Read/executed | Entry points |

## Persistent Runtime Data

Must survive every release swap. Never shipped inside the Artifact; never
git-tracked (already `.gitignore`d — `/public/uploads/`, `/storage/uploads/`,
`/storage/backups/`, `/storage/advertisements/`). Lives outside the release
tree and is reattached to the new release by deployment-system, not created
on demand by the application.

| Path | Writer | Reader | Survive swap? | Belongs in Artifact? |
|---|---|---|---|---|
| `public/uploads` (`uploads` disk, `config/filesystems.php:69-76`) | `SmartImageUploadService` via `MediaStorage::disk('uploads')`, `www-data` | Public, via `MediaStorage::url()` | **Yes — must** | No — symlink target |
| `storage/app/public` (`public` disk, legacy `uploads/...`-prefixed paths, `MediaStorage::disk('legacy')`) | Same upload path, legacy records only | Public, via `public/storage` symlink | Yes | No |
| `storage/app/private` (`private`/`local` disks) | `SupportController`, `TenantAttachmentService` | Never web-accessible | Yes | No |
| `storage/backups` | `BackupDatabaseCommand` | Ops/CLI only | Yes | No |
| `storage/installed.lock` | `InstallController` | `RedirectIfNotInstalled` middleware | Yes | No |
| `.env` | Deployment-system config step | `config()` at boot | Yes | No — secret |

`storage/advertisements` also exists on disk but has **no code reference**
anywhere in `app/` — it is legacy/orphaned. It is not part of this contract;
leave it alone (deleting or repurposing it is outside this task's scope).

## Build-Time Generated Content

Produced during the build phase, before `validate-release.php` runs, then
shipped as Immutable Release Content (not regenerated in production):

- `public/build/manifest.json`, `public/build/assets/*` (`npm run build`)
- `vendor/**` (`composer install --no-dev --optimize-autoloader`)

## Cache/Temporary Data

Disposable; safe to lose on every release; not part of the persistence
contract:

- `storage/framework/cache`, `storage/framework/sessions`, `storage/framework/views`
- `bootstrap/cache/*.php` (config/route/event cache)
- `storage/logs`

## Secrets/Environment State

- `.env` — applied by deployment-system as a configuration step, not shipped
  inside the Artifact source tree; config cache (`bootstrap/cache/config.php`)
  is rebuilt after `.env` changes, per `docs/production-stability-fixes/DEPLOYMENT_CHECKLIST.md`.

## Writable Paths

Only these need to be writable by the PHP-FPM (`www-data`) worker at
runtime. Nothing else under the release tree should be:

| Path | Writer |
|---|---|
| `public/uploads` (persistent root, reattached via symlink — see below) | `www-data` |
| `storage/app/public`, `storage/app/private` | `www-data` |
| `storage/framework/{cache,sessions,views}`, `storage/logs`, `bootstrap/cache` | `www-data` |

`public/`, `app/`, `vendor/`, `config/`, and every other release-content
directory must **not** be writable by `www-data`. The application must never
need write access to its own source tree to render a read-only page — that
was the actual defect (`MediaStorage::url()` used to require it implicitly).

## Ownership/Permissions

Least-privilege, no `chmod -R 777`:

| Path | Owner | Group | Dir mode | File mode |
|---|---|---|---|---|
| Release tree (`app/`, `vendor/`, `public/build`, ...) | `deployment-agent` | `deployment-agent` | `0755` | `0644` |
| Persistent uploads root (outside release tree) | `deployment-agent` | `www-data` | `2775` (setgid) | `0664` |
| `storage/`, `bootstrap/cache/` | `deployment-agent` | `www-data` | `2775` | `0664` |

Setgid (`2775`) on directories keeps new files/subdirectories group-owned by
`www-data` without needing a recursive `chown` after every write. This
mirrors the correction already documented (but never applied — see
[§ Why the previous fix didn't hold](#why-the-previous-fix-didnt-hold)) in
`docs/production-stability-fixes/AUDIT_REPORT.md`; the difference here is
*where* the writable root lives.

## Symlink/Persistence Rules

`public/uploads` must not be a real directory inside the release tree. The
minimum safe production contract:

```
/var/www/customer-site-shared/uploads/        <- persistent root, OUTSIDE the release tree
/var/www/customer-site/releases/<sha>/         <- immutable release (Artifact contents)
/var/www/customer-site/releases/<sha>/public/uploads  -> symlink -> /var/www/customer-site-shared/uploads
/var/www/customer-site/current -> releases/<sha>       <- atomic activation symlink
```

- `public/storage` already follows this exact pattern today
  (`config/filesystems.php:167`, `public/storage -> storage/app/public`,
  created by `storage:link`) — reuse the same mechanism/precedent for
  `public/uploads` rather than inventing a second one.
- The persistent uploads root is created **once**, out-of-band, with the
  ownership in the table above. Every release only needs its
  `public/uploads` symlink reattached during activation; the release itself
  never contains an `uploads/` directory.
- `storage/app/public`, `storage/app/private`, `storage/backups` already sit
  under `storage/`, which `docs/production-stability-fixes/DEPLOYMENT_CHECKLIST.md`
  already lists as preserved across releases — no new mechanism needed there
  either, only confirmation that `public/uploads` gets the same treatment.

## Activation Preconditions

Before a release may become `current`:

1. `scripts/validate-release.php <staged>` passes (Artifact completeness —
   vendor, build manifest, artisan chain; see
   [§ Artifact completeness](#artifact-completeness)).
2. The persistent uploads root exists at its fixed out-of-tree path with the
   ownership/mode in [§ Ownership/Permissions](#ownershippermissions). This is
   a one-time host precondition, not per-release.
3. `.env` for the release is in place (config cache rebuilt after).

## Post-Activation Preconditions

Immediately after the atomic `current` symlink swap:

1. `public/uploads` inside the new release resolves (via symlink) to the
   persistent root and is writable by `www-data`.
2. `public/storage` resolves to `storage/app/public` (already covered by
   existing `storage:link` handling).
3. `storage/`, `bootstrap/cache/` are writable by `www-data` in the new
   release (per the existing `writable_dirs` check in
   `scripts/validate-release.php`).
4. A smoke request against a page that renders media (e.g. the homepage) does
   not need to create any directory to succeed — verified by
   `tests/Feature/HomepageRendersWithUnusableUploadsRootTest.php` even when
   step 2 is somehow unmet, but step 2 is still required for uploads to
   actually persist/render.

## Failure/Rollback Expectations

- If the persistent uploads root or its symlink is missing:
  reads degrade safely (`ImageHelper::url()` → `null`, no thumbnail — this
  fix), writes fail loudly (`MediaStorage::disk()` propagates; controllers
  log and return a visible error, existing DB paths are preserved — already
  covered by `ProfileAvatarStorageRegressionTest` /
  `AdminProfileImageUploadTest` / the new `ProfileAvatarRealStorageFailureTest`).
  The site stays up; uploads are degraded, not the whole application.
- A release that fails `validate-release.php` must not activate; the
  previously active release stays intact (existing `tests/ReleaseGate`
  coverage).
- A release swap must never delete or move the persistent uploads root —
  only the release tree's symlink to it changes. Deployment-system must
  treat that root the same way it already treats `storage/`: reattached,
  never recreated from the Artifact.

<a id="artifact-completeness"></a>
## Artifact Completeness (previous vendor incident)

Already closed by `scripts/validate-release.php` / `docs/RELEASE_GATE.md`
(11 fail-closed checks: PHP platform requirement, `composer.json`,
`vendor/autoload.php`, `composer.lock`, `vendor/composer/installed.json`,
internal `saas-platform/*` packages self-contained, `public/build/manifest.json`
+ assets, artisan chain, artisan syntax, autoload boots in an isolated
subprocess, `storage/`+`bootstrap/cache` writable). One authoritative rule,
unchanged by this task:

**The Artifact is self-contained. Production never runs `composer install`
or a frontend build.**

<a id="why-the-previous-fix-didnt-hold"></a>
## Why the previous fix didn't hold

`docs/production-stability-fixes/AUDIT_REPORT.md` (2026-08-22) already
diagnosed `public/uploads` ownership as wrong and prescribed:

```bash
chown -R deployment-agent:www-data /var/www/customer-site/public/uploads
find /var/www/customer-site/public/uploads -type d -exec chmod 2775 {} +
find /var/www/customer-site/public/uploads -type f -exec chmod 0664 {} +
```

That correction — even if applied — lived **inside the release tree**. The
next atomic release swap replaced the tree wholesale, taking the corrected
ownership (and the uploaded files themselves) with it. That is exactly what
this incident's evidence shows: `public/uploads` missing entirely from the
new release, previous-release backup still holding the real files. A
permission fix inside an immutable release cannot outlive that release. The
symlink-to-an-external-root pattern in this document is the fix that
survives release replacement; a repeated `chown`/`chmod` inside `public/uploads`
is not.
