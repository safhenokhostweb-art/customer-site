# Customer-Site Release Gate

> Referenced as the canonical Deployment Gate implementation by
> `saas-platform/docs/quality/TESTING_AND_DELIVERY_GOVERNANCE.md` §9. This
> file remains the source of truth for its own content; the governance
> document does not duplicate it.

## Purpose

Prevents the recurring production incident class where an incomplete release
(missing `vendor/autoload.php` or `public/build/manifest.json`) became ACTIVE.
The release tree is now validated FAIL-CLOSED before it can be activated.

## How to run

```bash
# Validate the staged release tree (e.g. the built workspace or extracted artifact)
php scripts/validate-release.php /path/to/release --json

# Same, but write a release provenance manifest
php scripts/validate-release.php /path/to/release \
    --commit-sha=<sha> --manifest-out=release-manifest.json
```

Exit code 0 = pass, 1 = fail (DO NOT activate), 2 = usage error.
The gate never writes inside the release directory.

## Checks (fail-closed)

1. `php_version` — PHP satisfies composer.json platform requirement
2. `composer_manifest` — composer.json present
3. `vendor_autoload` — vendor/autoload.php present
4. `composer_lock` — composer.lock present (reproducibility)
5. `vendor_installed` — vendor/composer/installed.json present
6. `internal_packages` — every declared saas-platform/* package installed AND
   no vendor entry resolves outside the release root (rejects Windows Junctions
   /symlinks to the developer machine — a self-containment check)
7. `build_manifest` — public/build/manifest.json + assets present when Vite is
   configured (authoritative strategy: assets pre-built and shipped)
8. `artisan_chain` — artisan, bootstrap/app.php, public/index.php
9. `artisan_syntax` — php -l artisan passes
10. `autoload_boots` — Composer autoload loads in an isolated subprocess
11. `writable_dirs` — storage/ and bootstrap/cache/ exist and are writable

## Where it plugs in

Build -> composer install --no-dev --optimize-autoloader -> npm ci && npm run
build -> php scripts/validate-release.php <staged> -> immutable artifact ->
deploy -> server-side config/cache operations only.

Production never runs composer install / npm ci (no Packagist/GitHub
reachability); the artifact must be self-contained.

## Tests

```bash
php tests/ReleaseGate/run.php
```

Deterministic, framework-free. Covers: missing vendor rejected; missing Vite
manifest rejected; missing internal package rejected; valid artifact passes;
failed build cannot activate; target tree untouched on failure; junction/
symlink path dependency rejected; manifest records commit SHA + lock state.