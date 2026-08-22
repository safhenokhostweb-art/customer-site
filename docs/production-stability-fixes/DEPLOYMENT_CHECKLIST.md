# Production Stability Fixes — Deployment Checklist

Release scope: `customer-site` only. `saas-platform` has no source changes and its existing license validation endpoint is compatible. No database migration is included.

All artifact creation, configuration application, activation, rollback, and verification must run through `deployment-system`. Do not use the legacy deploy scripts, copy files manually, or patch production over SSH.

## Release files

### Runtime

- `app/Helpers/ImageHelper.php`
- `app/Http/Controllers/Account/ProfileAvatarController.php`
- `app/Http/Controllers/Admin/AdminProfileController.php`
- `app/Services/LicenseActivationService.php`
- `app/Services/SmartImageUploadService.php`
- `config/license.php`
- `resources/views/admin/layout.blade.php`
- `resources/views/admin/partials/license-transient-banner.blade.php`
- `resources/views/admin/profile/index.blade.php`

### Tests and release evidence

- `tests/Feature/Account/ProfileAvatarStorageRegressionTest.php`
- `tests/Feature/ProductionStability/AdminProfileImageUploadTest.php`
- `tests/Feature/ProductionStability/LicenseStabilityTest.php`
- `docs/production-stability-fixes/AUDIT_REPORT.md`
- `docs/production-stability-fixes/DEPLOYMENT_CHECKLIST.md`

## Pre-release gates

- [ ] Confirm the committed release diff contains only the files above.
- [ ] Confirm `saas-platform` is clean and requires no release.
- [ ] Run the focused stability/regression suite: expected result is 34 tests and 102 assertions.
- [ ] Run `php scripts/release-compatibility.php` against the clean committed source revision.
- [ ] Build the immutable artifact in the deployment-system isolated workspace.
- [ ] Require installed `vendor`, the Vite build manifest/assets, internal packages, and Composer lock consistency.
- [ ] Run `php scripts/validate-release.php <staged-release> --json --commit-sha=<release-sha>` before upload/activation.
- [ ] Verify the hub accepts the artifact as complete and its SHA-256 matches before atomic activation.
- [ ] Preserve `.env`, `storage`, and `public/storage`; do not preserve `vendor` or `public/build` from the old release.
- [ ] Confirm a rollback release is available before activation.

## Required production configuration

Apply these through approved deployment-system steps during the same release. Fail the release if a required value or validation cannot be applied.

### Customer runtime

- [ ] Set `PLATFORM_URL=https://panel.safhenokhost.ir` (direct HTTPS; no HTTP-to-HTTPS redirect).
- [ ] Keep Bearer authentication enabled. The request-body credential is only the compatible fallback; conflicting credentials must still be rejected.
- [ ] Set `LICENSE_VERIFY_SSL=true` or remove the override so the public certificate is verified.
- [ ] Use the bounded defaults `LICENSE_CONNECT_TIMEOUT_SECONDS=2` and `LICENSE_SYNC_TIMEOUT_SECONDS=5` unless production already supplies equivalent values.
- [ ] Rebuild Laravel configuration cache after the environment update.
- [ ] Ensure the configured upload storage path is writable by the PHP-FPM worker. For the current local upload disk, directories require deployment ownership with `www-data` group access and setgid-compatible permissions; files require group write access.

### PHP-FPM capacity and observability

Apply to the active PHP 8.3 `www` pool after validating host capacity:

```ini
pm = dynamic
pm.max_children = 12
pm.start_servers = 3
pm.min_spare_servers = 2
pm.max_spare_servers = 4
pm.max_requests = 500
request_slowlog_timeout = 5s
slowlog = /var/log/php8.3-fpm-slow.log
```

- [ ] Validate with `php-fpm8.3 -tt` before reload.
- [ ] Ensure the slowlog path is creatable/writable by the PHP-FPM service under the host's log policy.
- [ ] Reload PHP-FPM through the deployment-system service action; verify it becomes active.
- [ ] Reload Nginx only after its configuration test passes; verify it becomes active.
- [ ] Do not increase Nginx, FastCGI, CDN, or application timeout values as a substitute for correcting worker saturation.

## Post-deploy verification

### Profile upload

- [ ] Upload a real avatar from the account profile and admin profile flows.
- [ ] Confirm the stored path is canonical, the image URL returns HTTP 200, and the new image renders.
- [ ] Force or simulate a storage failure and confirm it is logged, a visible error is returned, and the previous image/path remains unchanged.
- [ ] Confirm a legacy `uploads/admin-avatars/...` image still renders.

### License

- [ ] Confirm license validation connects directly to `https://panel.safhenokhost.ir` with certificate verification.
- [ ] Confirm Bearer authentication reaches the platform and no redirect-related 401 occurs.
- [ ] Confirm a valid cached license remains active during a timeout, DNS error, 5xx response, or transport 401.
- [ ] Confirm no false warning banner appears for a valid cached license.
- [ ] Confirm definite expired, revoked, invalid-key, domain-mismatch, and inactive-site responses still fail closed.

### Infrastructure and logs

- [ ] Confirm PHP-FPM is active and reports the intended pool settings.
- [ ] Confirm Nginx is active and its configuration test passes.
- [ ] Confirm Laravel logs contain no new unhandled exceptions, license credential leakage, or recurring avatar storage errors.
- [ ] Confirm the PHP-FPM slowlog is active; investigate entries rather than masking them with longer timeouts.
- [ ] Monitor worker RSS and `pm.max_children` saturation warnings under representative traffic.
- [ ] Confirm customer health/login endpoints pass through the deployment-system health gate.
- [ ] Confirm there are no recurring origin/upstream or ArvanCloud 504 responses during the observation window.

## Release acceptance

- [ ] Artifact completeness and release validation passed.
- [ ] All required deployment-system tasks succeeded; no gate was bypassed.
- [ ] Profile, license, PHP-FPM, Nginx, Laravel log, and 504 checks passed.
- [ ] Record the release SHA, artifact checksum, deployment ID, configuration task results, health-check evidence, and rollback reference in the deployment record.
