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

The user authorized version-specific known baseline issues. The machine
registry is `known-issues.canvas-homepage.json`; the canonical human entrypoint
is `../docs/KNOWN-TEST-FAILURES.md`. `scripts/verify-release-known-issues.php`
checks version, source commits, a maximum 14-day approval, every required
source fingerprint, and exact method-level PHPUnit group membership before
excluding the four named inherited cases. It rejects class-wide grouping,
unknown methods, expired approval and source changes. All other failures
remain blocking. The original four menu/typography expectations are corrected
and still run normally. Production runtime code is unchanged.

The deployment artifact still must pass the canonical validator documented in
`../docs/RELEASE_GATE.md` before activation:

```sh
php scripts/validate-release.php /path/to/staged-release --commit-sha=<exact-sha> --json
```

The descriptor's two homepage source hashes are provenance. The Hub's Release,
Artifact and Deployment records carry the exact SHA of subsequent metadata
commits. Secrets and environment-specific endpoints remain outside the tracked
descriptor and policy.
