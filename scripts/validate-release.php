<?php

declare(strict_types=1);

/**
 * validate-release.php — FAIL-CLOSED release gate for customer-site artifacts.
 *
 * Root cause this gate prevents (recurring production incidents):
 *   An incomplete release (missing vendor/autoload.php or public/build/
 *   manifest.json) became ACTIVE because activation trusted the artifact's
 *   checksum but never verified the COMPLETENESS of the final tree.
 *
 * Contract:
 *   Invoke between "build" and "immutable artifact", and re-verify against
 *   the staged tree immediately before activation. Non-zero exit means the
 *   release MUST NOT be activated; the current active release stays intact.
 *
 * Usage:
 *   php scripts/validate-release.php <target-dir> [--json]
 *       [--commit-sha=<sha>] [--manifest-out=<path>] [--strict-vite]
 *
 * Exit code: 0 = pass; 1 = at least one required check failed.
 * The gate NEVER writes to <target-dir>; all checks are read-only.
 */

$args = array_slice($argv, 1);
$target = null;
$asJson = false;
$commitSha = '';
$manifestOut = null;
$strictVite = false;

foreach ($args as $arg) {
    if ($arg === '--json') { $asJson = true; continue; }
    if ($arg === '--strict-vite') { $strictVite = true; continue; }
    if (str_starts_with($arg, '--commit-sha=')) { $commitSha = substr($arg, strlen('--commit-sha=')); continue; }
    if (str_starts_with($arg, '--manifest-out=')) { $manifestOut = substr($arg, 15); continue; }
    if ($target === null) { $target = $arg; continue; }
    fwrite(STDERR, "validate-release: unknown argument: {$arg}\n");
    exit(2);
}

if ($target === null || !is_dir($target)) {
    fwrite(STDERR, "validate-release: target directory is required and must exist.\n");
    exit(2);
}
$target = rtrim($target, '/\\');

function runProcess(array $command, ?string $cwd = null, int $timeoutSeconds = 60): array
{
    $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = proc_open($command, $descriptors, $pipes, $cwd);
    if (!is_resource($proc)) {
        return ['exit' => 255, 'stdout' => '', 'stderr' => 'proc_open failed'];
    }
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $stdout = '';
    $stderr = '';
    $deadline = microtime(true) + $timeoutSeconds;
    while (true) {
        $read = [];
        if (!feof($pipes[1])) { $read[] = $pipes[1]; }
        if (!feof($pipes[2])) { $read[] = $pipes[2]; }
        if ($read === []) { break; }
        $write = null; $except = null;
        $ready = @stream_select($read, $write, $except, 0, 200000);
        if ($ready === false) { break; }
        if ($ready > 0) {
            foreach ($read as $stream) {
                $chunk = stream_get_contents($stream);
                if ($chunk === false || $chunk === '') { continue; }
                if ($stream === $pipes[1]) { $stdout .= $chunk; } else { $stderr .= $chunk; }
            }
        }
        $status = proc_get_status($proc);
        if (!$status['running']) { break; }
        if (microtime(true) > $deadline) {
            proc_terminate($proc);
            fclose($pipes[1]); fclose($pipes[2]);
            proc_close($proc);
            return ['exit' => 124, 'stdout' => $stdout, 'stderr' => 'timeout'];
        }
    }
    fclose($pipes[1]); fclose($pipes[2]);
    return ['exit' => proc_close($proc), 'stdout' => $stdout, 'stderr' => $stderr];
}

function canonPath(string $path): string
{
    $real = realpath($path);
    $path = $real !== false ? $real : $path;
    return rtrim(str_replace('\\', '/', $path), '/');
}

function jsonRead(string $path): ?array
{
    if (!is_file($path)) { return null; }
    $decoded = json_decode((string) file_get_contents($path), true);
    return is_array($decoded) ? $decoded : null;
}

function phpConstraintSatisfied(string $version, string $constraint): bool
{
    foreach (preg_split('/\s*\|\|\s*/', trim($constraint)) ?: [] as $alternative) {
        $tokens = preg_split('/(?:\s*,\s*|\s+)/', trim($alternative), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $matches = $tokens !== [];

        foreach ($tokens as $token) {
            if (str_starts_with($token, '^')) {
                $lower = substr($token, 1);
                $parts = array_map('intval', explode('.', $lower));
                if (($parts[0] ?? 0) > 0 || count($parts) === 1) {
                    $upper = (($parts[0] ?? 0) + 1) . '.0.0';
                } elseif (($parts[1] ?? 0) > 0 || count($parts) === 2) {
                    $upper = '0.' . (($parts[1] ?? 0) + 1) . '.0';
                } else {
                    $upper = '0.0.' . (($parts[2] ?? 0) + 1);
                }
                $matches = $matches
                    && version_compare($version, $lower, '>=')
                    && version_compare($version, $upper, '<');
                continue;
            }

            if (str_starts_with($token, '~')) {
                $lower = substr($token, 1);
                $parts = array_map('intval', explode('.', $lower));
                $upper = count($parts) >= 3
                    ? ($parts[0] ?? 0) . '.' . (($parts[1] ?? 0) + 1) . '.0'
                    : (($parts[0] ?? 0) + 1) . '.0.0';
                $matches = $matches
                    && version_compare($version, $lower, '>=')
                    && version_compare($version, $upper, '<');
                continue;
            }

            if (preg_match('/^(>=|<=|>|<|==|=)?\s*([0-9]+(?:\.[0-9]+){0,2})$/', $token, $m) === 1) {
                $matches = $matches && version_compare($version, $m[2], $m[1] ?: '==');
                continue;
            }

            if (preg_match('/^([0-9]+)(?:\.([0-9]+))?\.(?:x|\*)$/i', $token, $m) === 1) {
                $matches = $matches
                    && (int) explode('.', $version)[0] === (int) $m[1]
                    && (!isset($m[2]) || (int) explode('.', $version)[1] === (int) $m[2]);
                continue;
            }

            return false; // Unsupported syntax must fail closed.
        }

        if ($matches) {
            return true;
        }
    }

    return false;
}
// ---------------------------------------------------------------------------
// Checks (fixed order -> deterministic output)
// ---------------------------------------------------------------------------

$checks = [];
$failures = [];

function check(string $key, string $label, callable $run, array &$checks, array &$failures): void
{
    try {
        $result = $run();
        if ($result === true) {
            $checks[$key] = ['status' => 'pass', 'message' => $label];
        } else {
            $checks[$key] = ['status' => 'fail', 'message' => $result];
            $failures[] = $key;
        }
    } catch (\Throwable $e) {
        $checks[$key] = ['status' => 'fail', 'message' => $label . ' (exception: ' . $e->getMessage() . ')'];
        $failures[] = $key;
    }
}

// 1. PHP platform requirement
check('php_version', 'PHP platform requirement satisfied', function () use ($target) {
    $composer = jsonRead($target . '/composer.json');
    $requiredPhp = $composer['require']['php'] ?? null;
    if ($requiredPhp === null) {
        return 'composer.json declares no php requirement';
    }
    if (!phpConstraintSatisfied(PHP_VERSION, (string) $requiredPhp)) {
        return 'PHP ' . PHP_VERSION . ' does not satisfy composer.json requirement ' . $requiredPhp;
    }
    return true;
}, $checks, $failures);

// 2. composer.json present
check('composer_manifest', 'composer.json present in release', function () use ($target) {
    if (!is_file($target . '/composer.json')) {
        return 'composer.json is missing from the release root';
    }
    return true;
}, $checks, $failures);

// 3. vendor/autoload.php present
check('vendor_autoload', 'vendor/autoload.php present', function () use ($target) {
    if (!is_file($target . '/vendor/autoload.php')) {
        return 'vendor/autoload.php is MISSING from the release';
    }
    return true;
}, $checks, $failures);

// 4. composer.lock present (reproducibility)
check('composer_lock', 'composer.lock present for reproducible installs', function () use ($target) {
    if (!is_file($target . '/composer.lock')) {
        return 'composer.lock is missing from the release';
    }
    return true;
}, $checks, $failures);

// 5. vendor/composer/installed.json present
check('vendor_installed', 'vendor/composer/installed.json present', function () use ($target) {
    if (!is_file($target . '/vendor/composer/installed.json')) {
        return 'vendor/composer/installed.json is MISSING — composer install is incomplete';
    }
    return true;
}, $checks, $failures);

// 6. Internal path-repository packages present + self-contained
check('internal_packages', 'all declared internal packages present and self-contained', function () use ($target) {
    $composer = jsonRead($target . '/composer.json');
    if ($composer === null) {
        return 'cannot read composer.json';
    }
    $root = canonPath($target);
    $vendor = $target . '/vendor';
    if (!is_dir($vendor)) {
        return 'vendor/ directory is missing';
    }
    $missing = [];
    foreach (array_keys($composer['require'] ?? []) as $package) {
        if (!str_starts_with($package, 'saas-platform/')) {
            continue;
        }
        if (!is_dir($vendor . '/' . $package)) {
            $missing[] = $package;
        }
    }
    if ($missing !== []) {
        return 'internal packages missing from vendor/: ' . implode(', ', $missing);
    }
    // No vendor entry may resolve OUTSIDE the artifact root (junction/symlink
    // pointing at the developer machine is not a self-contained release).
    // NOTE: recursive scandir walk (NOT RecursiveDirectoryIterator): plain
    // is_dir() follows a Windows Junction (SplFileInfo::isDir() does not),
    // and realpath() resolves a junction to its OUTSIDE target — rejected.
    $escaped = [];
    $scan = function (string $dir, int $depth) use (&$scan, &$escaped, $root, $target): void {
        if (count($escaped) >= 20 || $depth > 12) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (!is_dir($path)) {
                continue;
            }
            $canon = canonPath($path);
            if ($canon !== '' && ($canon === $root || str_starts_with($canon, $root . '/'))) {
                $scan($path, $depth + 1);
                continue;
            }
            $escaped[] = str_replace('\\', '/', substr($path, strlen($target) + 1));
            if (count($escaped) >= 20) {
                return;
            }
        }
    };
    $scan($vendor, 0);
    if ($escaped !== []) {
        return 'vendor contains paths resolving outside the release (junctions/symlinks to the dev machine): ' . implode(', ', $escaped);
    }
    return true;
}, $checks, $failures);
// 7. Frontend build manifest (authoritative strategy: pre-built, shipped)
check('build_manifest', 'public/build/manifest.json present when Vite is used', function () use ($target, $strictVite) {
    $usesVite = $strictVite
        || is_file($target . '/vite.config.js')
        || is_file($target . '/vite.config.ts')
        || (is_file($target . '/package.json') && is_dir($target . '/resources/js'));
    if (!$usesVite) {
        return 'no Vite usage detected — frontend manifest not required for this release';
    }
    if (!is_file($target . '/public/build/manifest.json')) {
        return 'public/build/manifest.json is MISSING — run "npm ci && npm run build" before creating the artifact';
    }
    $assets = $target . '/public/build/assets';
    if (!is_dir($assets) || (count(scandir($assets) ?: []) <= 2)) {
        return 'public/build/assets/ is empty — the Vite build produced no asset files';
    }
    return true;
}, $checks, $failures);

// 8. Artisan chain (Laravel entry files)
check('artisan_chain', 'artisan, bootstrap/app.php, public/index.php present', function () use ($target) {
    $composer = jsonRead($target . '/composer.json');
    if (($composer['require']['laravel/framework'] ?? null) === null) {
        return 'not a Laravel project — artisan chain not required';
    }
    foreach (['artisan', 'bootstrap/app.php', 'public/index.php'] as $required) {
        if (!is_file($target . '/' . $required)) {
            return $required . ' is MISSING from the release';
        }
    }
    return true;
}, $checks, $failures);

// 9. Artisan syntax check (php -l)
check('artisan_syntax', 'artisan passes php -l', function () use ($target) {
    if (!is_file($target . '/artisan')) {
        return true; // covered by artisan_chain when Laravel
    }
    $result = runProcess([PHP_BINARY, '-l', $target . '/artisan']);
    if ($result['exit'] !== 0) {
        return 'artisan syntax check failed: ' . trim($result['stdout'] . $result['stderr']);
    }
    return true;
}, $checks, $failures);

// 10. Composer autoload actually boots (isolated subprocess)
check('autoload_boots', 'composer autoload boots in an isolated subprocess', function () use ($target) {
    $autoload = $target . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        return 'vendor/autoload.php missing (see vendor_autoload)';
    }
    $code = 'require ' . var_export($autoload, true) . '; echo "AUTOLOAD_OK";';
    $result = runProcess([PHP_BINARY, '-r', $code]);
    if ($result['exit'] !== 0 || !str_contains($result['stdout'], 'AUTOLOAD_OK')) {
        return 'composer autoload failed to boot: ' . trim($result['stdout'] . $result['stderr']);
    }
    return true;
}, $checks, $failures);

// 11. Required writable directories exist
check('writable_dirs', 'storage/ and bootstrap/cache/ exist and are writable', function () use ($target) {
    foreach (['storage', 'bootstrap/cache'] as $dir) {
        $path = $target . '/' . $dir;
        if (!is_dir($path)) {
            return $dir . '/ is MISSING from the release';
        }
        if (!is_writable($path)) {
            return $dir . '/ is not writable';
        }
    }
    return true;
}, $checks, $failures);
// ---------------------------------------------------------------------------
// Manifest (release provenance)
// ---------------------------------------------------------------------------

$composerLockSha = '';
if (is_file($target . '/composer.lock')) {
    $composerLockSha = (string) hash_file('sha256', $target . '/composer.lock');
}

$manifest = [
    'format'               => 'deployment-system-release-manifest',
    'version'              => 1,
    'project_key'          => 'customer-site',
    'commit_sha'           => $commitSha,
    'composer_lock_sha256' => $composerLockSha,
    'php'                  => PHP_VERSION,
    'generated_at'         => gmdate('c'),
    'status'               => $failures === [] ? 'pass' : 'fail',
    'checks'               => $checks,
];

if ($manifestOut !== null) {
    $dir = dirname($manifestOut);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($manifestOut, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// ---------------------------------------------------------------------------
// Output
// ---------------------------------------------------------------------------

$ok = $failures === [];

if ($asJson) {
    echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
} else {
    echo 'validate-release: ' . ($ok ? 'PASS' : 'FAIL') . "  (target: {$target})\n";
    foreach ($checks as $key => $result) {
        $mark = $result['status'] === 'pass' ? '  [OK]' : '  [FAIL]';
        echo $mark, ' ', $key, ' — ', $result['message'], "\n";
    }
    if (!$ok) {
        echo 'validate-release: DO NOT ACTIVATE this release. Missing/blocked: ', implode(', ', $failures), "\n";
    }
}

exit($ok ? 0 : 1);
