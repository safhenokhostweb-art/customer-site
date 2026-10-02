<?php

declare(strict_types=1);

/** Version-bound baseline registry validation; it never changes a test result. */
final class ReleaseKnownIssues
{
    public const SOURCE_FILES = [
        'resources/views/admin/theme-design/canvas-editor.blade.php',
        'resources/views/admin/theme-design/canvas-live-preview.blade.php',
        'resources/views/canvas-preview.blade.php',
        'resources/views/layouts/canvas-theme.blade.php',
        'app/Http/Controllers/Admin/ThemeDesignController.php',
        'app/Services/ThemeCanvas/ThemeRuntimeDocument.php',
        'tests/Feature/ThemeCanvas/CanvasPostMessageOriginTest.php',
        'tests/Feature/ThemeCanvas/CanvasRuntimeParityTest.php',
        'vendor/saas-platform/modules-theme-runtime/resources/views/canvas/runtime.blade.php',
    ];

    public static function validate(array $descriptor, array $registry, string $root, array $registeredTests, DateTimeImmutable $now): array
    {
        $require = static function (bool $condition, string $message): void {
            if (! $condition) {
                throw new RuntimeException($message);
            }
        };
        $require(($registry['schema_version'] ?? null) === 1, 'Unsupported known-issue schema.');
        $require(($registry['release_version'] ?? null) === ($descriptor['version'] ?? null), 'Known issues belong to a different release version.');
        $require(($registry['source_commits'] ?? null) === ($descriptor['source_commits'] ?? null), 'Known-issue source commits do not match the release.');
        $require(($registry['group'] ?? null) === 'canvas-homepage-baseline-2026-10-02', 'Unapproved baseline group.');
        $require(($registry['fingerprint_format'] ?? null) === 'sha256-utf8-lf-v1', 'Unsupported source fingerprint format.');
        $require(is_string($registry['recorded_at'] ?? null) && is_string($registry['expires_at'] ?? null), 'Known-issue timestamps are required.');
        $recorded = new DateTimeImmutable($registry['recorded_at']);
        $expires = new DateTimeImmutable($registry['expires_at']);
        $require($recorded <= $now && $now < $expires && $expires <= $recorded->modify('+14 days'), 'Known-issue approval is expired, future-dated or exceeds 14 days.');
        $issues = $registry['issues'] ?? [];
        $require(is_array($issues) && count($issues) === 4, 'This release approves exactly four named baseline cases.');
        $tests = [];
        $ids = [];
        foreach ($issues as $issue) {
            $require(is_string($issue['test'] ?? null) && preg_match('/^Tests\\\\Feature\\\\ThemeCanvas\\\\[A-Za-z0-9]+Test::test_[a-z0-9_]+$/D', $issue['test']) === 1, 'Only exact ThemeCanvas test methods are allowed.');
            $require(! in_array($issue['test'], $tests, true) && ! in_array($issue['id'], $ids, true), 'Duplicate known-issue test or identity.');
            $require(is_string($issue['id'] ?? null) && is_string($issue['reason'] ?? null) && is_string($issue['owner'] ?? null) && trim($issue['reason']) !== '' && trim($issue['owner']) !== '', 'Known issues need an identity, owner and reason.');
            $tests[] = $issue['test'];
            $ids[] = $issue['id'];
        }
        sort($tests);
        sort($registeredTests);
        $require($tests === $registeredTests, 'Registered baseline groups must match the manifest exactly.');
        $files = $registry['source_files'] ?? [];
        $require(is_array($files) && $files !== [], 'Baseline source fingerprints are required.');
        $paths = array_keys($files);
        $requiredPaths = self::SOURCE_FILES;
        sort($paths);
        sort($requiredPaths);
        $require($paths === $requiredPaths, 'Every approved baseline source fingerprint is mandatory.');
        foreach ($files as $path => $digest) {
            $require(preg_match('#^(resources/views/|app/Http/Controllers/|app/Services/ThemeCanvas/|tests/Feature/ThemeCanvas/|vendor/saas-platform/modules-theme-runtime/resources/views/)[A-Za-z0-9_./-]+$#D', $path) === 1 && ! str_contains($path, '..'), 'Unsafe baseline source path.');
            $require(is_string($digest) && preg_match('/^[a-f0-9]{64}$/D', $digest) === 1, 'Invalid baseline source fingerprint.');
            $file = realpath($root.'/'.$path);
            $base = realpath($root);
            $require($file !== false && $base !== false && str_starts_with(str_replace('\\', '/', $file), str_replace('\\', '/', $base).'/'), 'Baseline source escapes the release root or is missing.');
            $contents = str_replace(["\r\n", "\r"], "\n", file_get_contents($file));
            $require(hash_equals($digest, hash('sha256', $contents)), 'Baseline source changed: '.$path.'; remove or re-review its known issue.');
        }
        return $tests;
    }
}
