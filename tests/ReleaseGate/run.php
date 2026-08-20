<?php

declare(strict_types=1);

/**
 * Deterministic, standalone test suite for scripts/validate-release.php.
 *
 * Usage:
 *   php tests/ReleaseGate/run.php
 *
 * Builds self-contained fixture release trees in a temp directory, invokes
 * the gate as a subprocess, and asserts the eight required release-gate
 * scenarios. No framework dependency; runs with plain PHP 8.2+.
 */

$repoRoot = dirname(__DIR__, 2);
$gate = $repoRoot . '/scripts/validate-release.php';
$php = PHP_BINARY;

if (!is_file($gate)) {
    fwrite(STDERR, "Gate script not found: {$gate}\n");
    exit(2);
}

$base = sys_get_temp_dir() . '/release-gate-' . bin2hex(random_bytes(4));
mkdir($base, 0700, true);

$results = [];
$failures = [];

function record(string $name, bool $ok, string $detail): void
{
    global $results, $failures;
    $results[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
    if (!$ok) {
        $failures[] = $name;
    }
}

function run(string $command, array $args): array
{
    $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = proc_open(array_merge([$command], $args), $descriptors, $pipes);
    if (!is_resource($proc)) {
        return ['exit' => 255, 'stdout' => '', 'stderr' => 'proc_open failed'];
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['exit' => proc_close($proc), 'stdout' => (string) $stdout, 'stderr' => (string) $stderr];
}

function treeHash(string $dir): string
{
    $files = [];
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($iter as $file) {
        if ($file->isFile()) {
            $files[] = str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1)) . ':' . hash_file('sha256', $file->getPathname());
        }
    }
    sort($files, SORT_STRING);
    return hash('sha256', implode("\n", $files));
}

function removeTree(string $dir): void
{
    if (!is_dir($dir)) {
        if (is_file($dir) || is_link($dir)) {
            @unlink($dir);
        }
        return;
    }
    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        removeTree($dir . '/' . $entry);
    }
    @rmdir($dir);
}

function runGate(string $fixture, array $extraArgs = []): array
{
    global $gate, $php;
    $result = run($php, array_merge([$gate, $fixture, '--json'], $extraArgs));
    $result['json'] = json_decode($result['stdout'], true);
    return $result;
}

function gateJson($result): array
{
    return is_array($result['json']) ? $result['json'] : [];
}
// ---------------------------------------------------------------------------
// Fixture builder — minimal, VALID self-contained Laravel-like release
// ---------------------------------------------------------------------------

function buildValidFixture(string $root): string
{
    $dir = $root . '/valid-' . bin2hex(random_bytes(3));
    mkdir($dir . '/bootstrap/cache', 0755, true);
    mkdir($dir . '/storage', 0755, true);
    mkdir($dir . '/resources/js', 0755, true);
    mkdir($dir . '/public/build/assets', 0755, true);
    mkdir($dir . '/vendor/composer', 0755, true);
    foreach (['core-utils', 'modules-tickets', 'modules-forms', 'modules-crm', 'modules-seo-advanced'] as $pkg) {
        mkdir($dir . '/vendor/saas-platform/' . $pkg, 0755, true);
        file_put_contents(
            $dir . '/vendor/saas-platform/' . $pkg . '/composer.json',
            json_encode(['name' => 'saas-platform/' . $pkg, 'type' => 'library'], JSON_PRETTY_PRINT)
        );
    }

    file_put_contents($dir . '/composer.json', json_encode([
        'name' => 'laravel/laravel',
        'require' => [
            'php' => '^8.2',
            'laravel/framework' => '^12.0',
            'saas-platform/core-utils' => '@dev',
            'saas-platform/modules-tickets' => '@dev',
            'saas-platform/modules-forms' => '@dev',
            'saas-platform/modules-crm' => '@dev',
            'saas-platform/modules-seo-advanced' => '@dev',
        ],
    ], JSON_PRETTY_PRINT));
    file_put_contents($dir . '/composer.lock', json_encode(['packages' => []], JSON_PRETTY_PRINT));
    file_put_contents($dir . '/artisan', "<?php echo \"artisan fixture\\n\";\n");
    file_put_contents($dir . '/bootstrap/app.php', "<?php return new class {};\n");
    file_put_contents($dir . '/public/index.php', "<?php require __DIR__ . '/../vendor/autoload.php';\n");
    file_put_contents($dir . '/vendor/autoload.php', "<?php // fixture autoload stub\n");
    file_put_contents($dir . '/vendor/composer/installed.json', json_encode(['packages' => []], JSON_PRETTY_PRINT));
    file_put_contents($dir . '/public/build/manifest.json', json_encode(['fixture/app.js' => ['file' => 'assets/fixture.js']], JSON_PRETTY_PRINT));
    file_put_contents($dir . '/public/build/assets/fixture.js', "console.log('fixture');\n");
    file_put_contents($dir . '/resources/js/app.js', "import './fixture';\n");
    file_put_contents($dir . '/package.json', json_encode(['scripts' => ['build' => 'vite build']], JSON_PRETTY_PRINT));
    file_put_contents($dir . '/vite.config.js', "export default {};\n");
    file_put_contents($dir . '/storage/.gitkeep', '');
    file_put_contents($dir . '/bootstrap/cache/.gitkeep', '');
    return $dir;
}

// ---------------------------------------------------------------------------
// Scenarios 1-4
// ---------------------------------------------------------------------------

// S1 — release without vendor/autoload.php is rejected
$fixture = buildValidFixture($base);
unlink($fixture . '/vendor/autoload.php');
$r = runGate($fixture);
$json = gateJson($r);
$blocked = ($r['exit'] === 1) && ($json['status'] === 'fail') && (($json['checks']['vendor_autoload']['status'] ?? '') === 'fail');
record('S1: release without vendor/autoload.php is rejected', $blocked, 'exit=' . $r['exit'] . ' status=' . ($json['status'] ?? 'n/a'));

// S2 — release without public/build/manifest.json is rejected
$fixture = buildValidFixture($base);
unlink($fixture . '/public/build/manifest.json');
$r = runGate($fixture);
$json = gateJson($r);
$blocked = ($r['exit'] === 1) && (($json['checks']['build_manifest']['status'] ?? '') === 'fail');
record('S2: release without public/build/manifest.json is rejected', $blocked, 'exit=' . $r['exit'] . ' status=' . ($json['status'] ?? 'n/a'));

// S3 — missing internal package is rejected
$fixture = buildValidFixture($base);
removeTree($fixture . '/vendor/saas-platform/modules-tickets');
$r = runGate($fixture);
$json = gateJson($r);
$blocked = ($r['exit'] === 1) && (($json['checks']['internal_packages']['status'] ?? '') === 'fail');
record('S3: missing internal package is rejected', $blocked, 'exit=' . $r['exit'] . ' status=' . ($json['status'] ?? 'n/a'));

// S4 — valid self-contained artifact passes
$fixture = buildValidFixture($base);
$r = runGate($fixture);
$json = gateJson($r);
$passed = ($r['exit'] === 0) && ($json['status'] === 'pass');
record('S4: valid self-contained artifact passes', $passed, 'exit=' . $r['exit'] . ' status=' . ($json['status'] ?? 'n/a'));
// ---------------------------------------------------------------------------
// Scenarios 5-8
// ---------------------------------------------------------------------------

// S5 — failed build cannot become active: gate exits non-zero (caller contract)
$fixture = buildValidFixture($base);
unlink($fixture . '/vendor/autoload.php');
$r = runGate($fixture);
record('S5: failed build cannot become active (non-zero gate exit)', $r['exit'] !== 0, 'exit=' . $r['exit']);

// S6 — previous active release remains intact when validation fails (read-only)
$fixture = buildValidFixture($base);
unlink($fixture . '/vendor/autoload.php');
$before = treeHash($fixture);
runGate($fixture);
$after = treeHash($fixture);
record('S6: target tree untouched when validation fails', $before === $after, 'hash stable=' . ($before === $after ? 'yes' : 'no'));

// S7 — dirty/local path dependency (junction/symlink) cannot silently leak in
$fixture = buildValidFixture($base);
$outside = $base . '/outside-pkg';
mkdir($outside, 0700, true);
file_put_contents($outside . '/composer.json', json_encode(['name' => 'saas-platform/core-utils'], JSON_PRETTY_PRINT));
$pkgLink = $fixture . '/vendor/saas-platform/core-utils';
removeTree($pkgLink);
if (DIRECTORY_SEPARATOR === '\\') {
    $outArr = [];
    $linkCode = 0;
    exec('mklink /J "' . $pkgLink . '" "' . $outside . '"', $outArr, $linkCode);
} else {
    run('php', ['-r', 'symlink(' . var_export($outside, true) . ', ' . var_export($pkgLink, true) . ');']);
}
$r = runGate($fixture);
$json = gateJson($r);
$blocked = ($r['exit'] === 1)
    && (($json['checks']['internal_packages']['status'] ?? '') === 'fail')
    && str_contains((string) ($json['checks']['internal_packages']['message'] ?? ''), 'outside');
record('S7: junction/symlink path dependency is rejected (self-containment)', $blocked, 'exit=' . $r['exit'] . ' detail=' . substr((string) ($json['checks']['internal_packages']['message'] ?? ''), 0, 120));

// S8 — release manifest records commit SHA and package/build state
$fixture = buildValidFixture($base);
$manifestPath = $base . '/manifest-8.json';
$r = runGate($fixture, ['--commit-sha=abc123def456', '--manifest-out=' . $manifestPath]);
$manifest = json_decode((string) @file_get_contents($manifestPath), true);
$ok = is_array($manifest)
    && ($manifest['commit_sha'] ?? '') === 'abc123def456'
    && !empty($manifest['composer_lock_sha256'])
    && ($manifest['status'] ?? '') === 'pass'
    && isset($manifest['checks']['vendor_autoload']);
record('S8: release manifest records commit SHA and package/build state', $ok, 'sha=' . ($manifest['commit_sha'] ?? 'n/a') . ' lock=' . substr((string) ($manifest['composer_lock_sha256'] ?? ''), 0, 12) . '...');

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------

echo "ReleaseGate — 8 scenarios\n";
echo str_repeat('-', 72), "\n";
foreach ($results as $r) {
    printf("%-72s %s\n", $r['name'], $r['ok'] ? 'PASS' : 'FAIL');
    if (!$r['ok']) {
        echo '    detail: ', $r['detail'], "\n";
    }
}
echo str_repeat('-', 72), "\n";
echo count($results) - count($failures), '/', count($results), ' scenarios passed', "\n";

removeTree($base);

exit($failures === [] ? 0 : 1);