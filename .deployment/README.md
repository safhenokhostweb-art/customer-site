# Project release contract

`release-policy.json` uses the deployment-system canonical
`deployment/release-prepare.php` workflow. See
`../../saas-platform/.deployment/README.md` for preparation and provenance.
Run from an isolated `main` worktree with development dependencies and the
isolated test database configured:

```sh
php ../deployment-system/deployment/release-prepare.php check
```

customer-site installs `saas-platform/packages/**` through Composer `path`
repositories, so `git_dependencies` pins the provider: a `saas-platform`
checkout must sit beside this release worktree (`{project_root}/../saas-platform`),
on `main`, clean, with no unpushed commits, or `check` blocks. Its exact HEAD
is recorded as `dependency_commits.saas-platform` in the release metadata.

The required checks run the committed `tests/Feature/ThemeCanvas/` suite,
the known-issue guard tests, and `tests/ReleaseGate/run.php`. Navigation
assertions inspect rendered text and link destinations, so saved editor props
do not cause false failures.

Known-issue exemptions are retired (2026-10-26): all four canvas-homepage
baseline cases (CANVAS-BASELINE-001…004) pass on committed code, so the
registry was removed and **every** ThemeCanvas test gates the release.
`scripts/verify-release-known-issues.php` keeps them retired. It fails closed
if `known-issues.canvas-homepage.json` reappears or any test or class carries
the `canvas-homepage-baseline-2026-10-02` group. It then runs the whole suite
with `--run-tests`, with no exclusions.

`--run-tests` uses the dedicated `customer_site_release_test` database (MySQL,
utf8mb4_unicode_ci; override with `RELEASE_TEST_DB_DATABASE`, which must end
in `_release_test`), never the shared development `customer_site_test`, so a
concurrent development suite cannot invalidate a release check. Prepare it
once per machine with `DB_DATABASE=customer_site_release_test php artisan
migrate:fresh --env=testing --force`.

The deployment artifact still must pass the canonical validator documented in
`../docs/RELEASE_GATE.md` before activation:

```sh
php scripts/validate-release.php /path/to/staged-release --commit-sha=<exact-sha> --json
```

The descriptor's two homepage source hashes are provenance. The Hub's Release,
Artifact and Deployment records carry the exact SHA of subsequent metadata
commits. Secrets and environment-specific endpoints remain outside the tracked
descriptor and policy.
