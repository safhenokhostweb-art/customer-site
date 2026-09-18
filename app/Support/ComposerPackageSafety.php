<?php

namespace App\Support;

/**
 * TASK 3/3 Phase 6.5F (BLD-33) — the ONE canonical authority for verifying
 * customer-site's Composer "path" repositories (the 7 saas-platform
 * packages this app consumes: core-utils, modules/{crm,forms,tickets,
 * seo-advanced,theme-runtime,extension-sdk}) can never again reproduce the
 * Phase 6.5E incident: a real, live `composer install` inside a sibling
 * git worktree deleted 1,091 tracked files from saas-platform's canonical
 * package source, because on Windows a Composer path-repository
 * `"options": {"symlink": true}` package materializes in vendor/ as a
 * directory Junction, and PHP's is_link() does not detect a Junction —
 * a cleanup/reinstall routine that treats it as an ordinary directory
 * recurses through it and deletes the real target underneath.
 *
 * The primary fix is NOT this class — it's composer.json itself declaring
 * "symlink": false (mirror mode) for all 7 path repositories, empirically
 * proven (Phase 6.5F, disposable sandbox test, never touching real
 * saas-platform source) to install as a genuinely independent file copy:
 * Composer's own install log says "Mirroring from ..." and deleting the
 * resulting vendor/ copy has zero effect on the source. Mirror mode alone
 * makes deletion-through-a-link structurally impossible, in every context
 * (canonical checkout, any worktree, any fresh clone) — there is no
 * Junction/symlink left to mis-classify.
 *
 * This class is the defense-in-depth layer on top of that: it fails loud,
 * BEFORE and AFTER every `composer install`/`composer update` (wired as
 * composer.json "pre-install-cmd"/"pre-update-cmd"/"post-install-cmd"/
 * "post-update-cmd" scripts — see composer.json's own comment), if:
 *  - a path repository's "symlink" option is ever re-enabled (true or
 *    simply absent, since Composer's own default is true),
 *  - a declared path repository doesn't resolve to a real directory
 *    (catches a fresh clone missing the saas-platform sibling, with a
 *    clear message, instead of a confusing downstream Composer error),
 *  - anything currently installed under vendor/saas-platform/* is
 *    link-like (a stale Junction from before this fix, or a manual
 *    override) — using the exact realpath-comparison technique this
 *    codebase's own deployment-system/local-agent/lib/SafeFilesystem.php
 *    already proved reliable (is_link()/readlink() are BOTH confirmed
 *    unreliable for a Windows Junction by that project's own direct
 *    testing) — never reimplemented differently here.
 *
 * Framework-free on purpose (no Laravel facades/helpers): this must run
 * correctly from a bare `php scripts/verify-composer-package-safety.php`
 * invocation as a Composer "pre-install-cmd" hook, which fires BEFORE
 * vendor/autoload.php exists on a first-ever install.
 */
final class ComposerPackageSafety
{
    /**
     * True if $path's own final path component is itself a
     * symlink/Junction (i.e. resolves somewhere OTHER than where it
     * lexically sits). Ported unchanged in technique from
     * deployment-system/local-agent/lib/SafeFilesystem::isLinkLikeEntry()
     * — that project's own docblock records is_link() returning false
     * for a real, confirmed Junction, and readlink() being worse than
     * useless (a non-false, realpath-like value for an ORDINARY
     * directory too). Comparing "where would this path be if its own
     * final component were not a reparse point" against "where does it
     * actually, fully resolve to" is the one technique proven reliable
     * across that project's repeated direct testing.
     */
    public static function isLinkLikeEntry(string $path): bool
    {
        $real = realpath($path);
        if ($real === false) {
            return false;
        }

        $parentReal = realpath(dirname($path));
        if ($parentReal === false) {
            return false;
        }

        $expectedIfOrdinary = rtrim($parentReal, '\\/') . DIRECTORY_SEPARATOR . basename($path);

        return $expectedIfOrdinary !== $real;
    }

    /**
     * @return array<int, array{type: string, url: string, symlink: mixed}>
     */
    public static function parsePathRepositories(string $composerJsonPath): array
    {
        $decoded = json_decode((string) @file_get_contents($composerJsonPath), true);
        if (!is_array($decoded) || !is_array($decoded['repositories'] ?? null)) {
            return [];
        }

        $repos = [];
        foreach ($decoded['repositories'] as $repository) {
            if (is_array($repository) && ($repository['type'] ?? null) === 'path' && is_string($repository['url'] ?? null) && $repository['url'] !== '') {
                $repos[] = [
                    'type' => 'path',
                    'url' => $repository['url'],
                    'symlink' => $repository['options']['symlink'] ?? null,
                ];
            }
        }

        return $repos;
    }

    /**
     * PRE-FLIGHT: composer.json itself must declare mirror mode
     * (symlink === false) for every path repository — Composer's own
     * default, when the option is omitted entirely, is `true`, so a
     * missing option is a violation too, not just an explicit `true`.
     *
     * @param array<int, array{type: string, url: string, symlink: mixed}> $repos
     * @return string[] human-readable violation messages, empty if safe
     */
    public static function verifyMirrorModeDeclared(array $repos): array
    {
        $violations = [];
        foreach ($repos as $repo) {
            if ($repo['symlink'] !== false) {
                $violations[] = sprintf(
                    'Path repository "%s" does not declare "options": {"symlink": false} — got %s. '
                        . 'This project requires mirror mode (a real file copy) for every path repository; '
                        . 'symlink mode materializes as a Windows Junction, which caused a real 1,091-file '
                        . 'deletion incident (Phase 6.5E). See docs/composer-package-safety.md.',
                    $repo['url'],
                    var_export($repo['symlink'], true),
                );
            }
        }

        return $violations;
    }

    /**
     * PRE-FLIGHT: every declared path repository must resolve, relative to
     * $composerJsonDir, to a real, existing directory — a clear, specific
     * failure (naming the exact missing path) instead of a confusing
     * downstream Composer error on a fresh clone missing the saas-platform
     * sibling checkout.
     *
     * @param array<int, array{type: string, url: string, symlink: mixed}> $repos
     * @return string[]
     */
    public static function verifyDeclaredPathsResolve(string $composerJsonDir, array $repos): array
    {
        $violations = [];
        foreach ($repos as $repo) {
            $resolved = rtrim($composerJsonDir, '\\/') . '/' . $repo['url'];
            if (!is_dir($resolved)) {
                $violations[] = sprintf(
                    'Path repository "%s" does not resolve to a real directory (expected: %s). '
                        . 'This project expects saas-platform to be checked out as a sibling directory '
                        . '(../saas-platform relative to this project) — see docs/composer-package-safety.md.',
                    $repo['url'],
                    $resolved,
                );
            }
        }

        return $violations;
    }

    /**
     * PRE-FLIGHT: a path repository URL must be a relative path with no
     * leading drive letter / root slash. Defense in depth alongside mirror
     * mode itself — an absolute URL would still mirror-copy safely under
     * "symlink": false, but allowing one at all invites exactly the kind
     * of "resolves to an unexpected external target" configuration this
     * class exists to rule out deterministically, not merely by
     * convention.
     *
     * @param array<int, array{type: string, url: string, symlink: mixed}> $repos
     * @return string[]
     */
    public static function verifyNoAbsolutePaths(array $repos): array
    {
        $violations = [];
        foreach ($repos as $repo) {
            if (str_starts_with($repo['url'], '/') || preg_match('#^[A-Za-z]:[\\\\/]#', $repo['url']) === 1) {
                $violations[] = sprintf(
                    'Path repository "%s" is an absolute path — only paths relative to composer.json are permitted.',
                    $repo['url'],
                );
            }
        }

        return $violations;
    }

    /**
     * POST-FLIGHT: nothing currently installed under vendor/<vendor-name>/*
     * for these path packages may be link-like. Vendor package directory
     * names for a Composer package "foo/bar" install to vendor/foo/bar —
     * $installedPaths is the caller-supplied, explicit list of exactly
     * those (no guessing/globbing), keeping this one canonical authority
     * in control of exactly what it checks.
     *
     * @param string[] $installedPaths absolute paths under vendor/ to check
     * @return string[]
     */
    public static function verifyInstalledPathsAreNotLinks(array $installedPaths): array
    {
        $violations = [];
        foreach ($installedPaths as $path) {
            if (is_dir($path) && self::isLinkLikeEntry($path)) {
                $violations[] = sprintf(
                    'Installed package path "%s" is a symlink/Junction, not a real directory copy. '
                        . 'This is exactly the condition that caused the Phase 6.5E 1,091-file deletion '
                        . 'incident. Delete vendor/ and re-run composer install with mirror mode restored '
                        . '(composer.json "options": {"symlink": false}).',
                    $path,
                );
            }
        }

        return $violations;
    }
}
