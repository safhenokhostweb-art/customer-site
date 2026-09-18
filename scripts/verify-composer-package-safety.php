<?php

/**
 * TASK 3/3 Phase 6.5F (BLD-33) — Composer script hook. Wired into
 * composer.json's "pre-install-cmd"/"pre-update-cmd"/"post-install-cmd"/
 * "post-update-cmd" so it runs on EVERY `composer install`/`composer
 * update`, from any entry point (developer CLI, CI, a wrapper script) —
 * the one enforcement boundary that doesn't depend on a developer
 * remembering to run a separate check. Bypassable only via the explicit,
 * visible `--no-scripts` flag, never accidentally.
 *
 * See App\Support\ComposerPackageSafety for the actual checks and full
 * incident context (Phase 6.5E: a real `composer install` inside a
 * sibling git worktree deleted 1,091 tracked files from saas-platform's
 * canonical package source through a Windows Junction).
 *
 * Deliberately does NOT bootstrap Laravel or rely on vendor/autoload.php
 * — this must run correctly as a "pre-install-cmd" on a first-ever
 * install, before vendor/ exists at all.
 *
 * Usage: php scripts/verify-composer-package-safety.php [pre|post]
 */

require __DIR__ . '/../app/Support/ComposerPackageSafety.php';

use App\Support\ComposerPackageSafety;

$mode = $argv[1] ?? 'pre';
$projectRoot = dirname(__DIR__);
$composerJsonPath = $projectRoot . '/composer.json';

$repos = ComposerPackageSafety::parsePathRepositories($composerJsonPath);

$violations = [];

// Pre- and post-flight both re-check the declared configuration itself —
// cheap, and catches a composer.json edit that reintroduced symlink mode
// even if nothing has been (re)installed yet to expose a live Junction.
$violations = array_merge($violations, ComposerPackageSafety::verifyMirrorModeDeclared($repos));
$violations = array_merge($violations, ComposerPackageSafety::verifyNoAbsolutePaths($repos));
$violations = array_merge($violations, ComposerPackageSafety::verifyDeclaredPathsResolve($projectRoot, $repos));

if ($mode === 'post') {
    // Only meaningful once Composer has actually installed something —
    // map each required saas-platform/* package name to its real
    // vendor/<vendor>/<package> install path explicitly (no globbing),
    // so this checks exactly the packages this project declares, nothing
    // guessed.
    $decoded = json_decode((string) @file_get_contents($composerJsonPath), true);
    $required = is_array($decoded['require'] ?? null) ? array_keys($decoded['require']) : [];
    $installedPaths = [];
    foreach ($required as $packageName) {
        if (str_starts_with($packageName, 'saas-platform/')) {
            $installedPaths[] = $projectRoot . '/vendor/' . $packageName;
        }
    }

    $violations = array_merge($violations, ComposerPackageSafety::verifyInstalledPathsAreNotLinks($installedPaths));
}

if ($violations !== []) {
    fwrite(STDERR, "\nComposer package safety check FAILED (Phase 6.5F guard):\n\n");
    foreach ($violations as $violation) {
        fwrite(STDERR, "  - {$violation}\n\n");
    }
    fwrite(STDERR, "See docs/composer-package-safety.md for the full incident history and required workflow.\n");

    exit(1);
}

fwrite(STDOUT, "Composer package safety check passed ({$mode}-flight, " . count($repos) . " path repositories).\n");

exit(0);
