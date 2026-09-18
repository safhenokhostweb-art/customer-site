# Composer package safety (saas-platform path repositories)

## What happened (Phase 6.5E incident)

customer-site consumes 7 shared packages that live in the `saas-platform`
repository (`core-utils`, and the `crm`/`extension-sdk`/`forms`/
`seo-advanced`/`theme-runtime`/`tickets` modules) via Composer `"type":
"path"` repositories declared in `composer.json`, pointing at
`../saas-platform/packages/saas-platform/...` (relative to this project).

Those repositories used to declare `"options": {"symlink": true}`. On
Windows, Composer implements that as a directory **Junction** — not a true
NTFS symlink. During a diagnostic session, `composer install` was run
inside an isolated git worktree of this repo, placed as a **sibling** of
`customer-site` (both directly under the same parent directory, exactly
like the real `saas-platform` checkout is). The relative path
`../saas-platform/...`, evaluated from that sibling location, resolved to
the *exact same absolute directory* as the real, canonical `saas-platform`
checkout — an accidental alias, not a different copy. A Composer
reinstall/reconciliation cycle then treated the resulting Junction as an
ordinary vendor-owned directory and **recursively deleted the real
saas-platform source behind it — 1,091 tracked files**, temporarily. They
were fully recovered (`git checkout`) with zero data loss, but the
underlying condition — any genuine Composer reinstall of these packages,
in *any* location, including the canonical one — could reproduce this.

Root cause, precisely: PHP's `is_link()` does not detect a Windows
Junction. A cleanup routine that assumes "not a link" means "an ordinary,
Composer-owned directory, safe to delete recursively" is wrong for a
Junction, and ends up deleting through it into whatever it actually points
at.

## The fix (Phase 6.5F)

All 7 path repositories now declare **`"options": {"symlink": false}`**
(Composer's "mirror mode"): `vendor/saas-platform/*` is a real, independent
**file copy**, never a Junction or symlink. Deleting it — for any reason,
in any Composer operation, in any location — can never touch the real
saas-platform source, because there is no link to recurse through anymore.
Proven empirically (Phase 6.5F) with a fully disposable synthetic fixture:
Composer's own install log says `Mirroring from ../fake-source`, and
deleting the resulting vendor copy leaves the source completely
untouched.

This is enforced automatically, not just documented: `composer.json`'s
`pre-install-cmd` / `pre-update-cmd` / `post-install-cmd` /
`post-update-cmd` scripts run `scripts/verify-composer-package-safety.php`
on **every** `composer install` / `composer update`, from any entry point
(developer CLI, CI, a setup script). It fails loudly (non-zero exit,
aborting the Composer operation) if:

- any path repository stops declaring `"symlink": false`,
- a path repository URL is absolute (not relative to `composer.json`),
- a declared path repository doesn't resolve to a real directory (a clear,
  named error instead of a confusing downstream Composer failure — e.g. a
  fresh clone missing the `saas-platform` sibling checkout),
- anything currently installed under `vendor/saas-platform/*` is
  link-like (a stale Junction from before this fix, or a manual
  `"symlink": true` override).

The single canonical implementation of all of the above lives in
`App\Support\ComposerPackageSafety` — the CLI script
(`scripts/verify-composer-package-safety.php`) and the permanent
regression suite (`tests/Unit/ComposerPackageSafetyTest.php`) both use it;
there is no second, competing implementation.

## What this means for your workflow

- **Required layout, unchanged**: `saas-platform` must still be checked
  out as a sibling of `customer-site` (`../saas-platform` relative to this
  project) for `composer install`/`composer update` to work at all.
- **Editing a shared package**: with mirror mode, editing a file directly
  in `saas-platform/packages/saas-platform/...` is **not** instantly
  visible in `customer-site` the way a live symlink would have been — run
  `composer update saas-platform/<package>` (or a full `composer update`)
  in `customer-site` to pick up the change. This is the one real workflow
  cost of this fix, traded deliberately for eliminating the deletion
  risk entirely (mirror mode is unconditionally safe; a live symlink/
  Junction is not, in any location).
- **Worktrees**: any git worktree of `customer-site` — sibling-placed or
  otherwise — is safe by the same mechanism. There is nothing
  worktree-specific to configure.
- **Never** re-enable `"symlink": true` for these repositories. The guard
  script will refuse to proceed if you do.
- **Never** copy saas-platform package *source* into `customer-site`'s own
  tree (e.g. a local `packages/` directory here) to "solve" the live-edit
  friction above — `saas-platform` remains the sole canonical owner of
  this source; `customer-site` only ever consumes a generated vendor copy.

## Bypassing the guard

`composer install --no-scripts` / `composer update --no-scripts` skips
these checks entirely — this is Composer's own standard, visible opt-out,
never an accidental one. There is no supported reason to use it for normal
development on this project.
