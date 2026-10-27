<?php

declare(strict_types=1);

/**
 * Known-issue exemptions for the canvas-homepage release are retired
 * (2026-10-26): every baseline case passes on committed code, so every
 * ThemeCanvas test gates the release. This guard keeps them retired: a
 * re-introduced registry or baseline group tag fails closed instead of
 * silently excluding tests again. It never changes a test result.
 */
final class ReleaseKnownIssues
{
    public const RETIRED_GROUP = 'canvas-homepage-baseline-2026-10-02';
    public const RETIRED_REGISTRY = '.deployment/known-issues.canvas-homepage.json';

    /**
     * @param  list<string>  $groupedTests  test methods carrying RETIRED_GROUP
     * @param  list<string>  $groupedClasses  test classes carrying RETIRED_GROUP
     */
    public static function assertNoExemptions(string $root, array $groupedTests, array $groupedClasses): void
    {
        if (is_file($root.'/'.self::RETIRED_REGISTRY)) {
            throw new RuntimeException('Known-issue exemptions are retired; '.self::RETIRED_REGISTRY.' must not exist. Fix the failing test instead of re-approving it.');
        }
        if ($groupedClasses !== []) {
            throw new RuntimeException('Baseline approval cannot exclude a whole test class: '.implode(', ', $groupedClasses));
        }
        if ($groupedTests !== []) {
            sort($groupedTests);
            throw new RuntimeException('Known-issue exemptions are retired; these tests still carry the '.self::RETIRED_GROUP.' group: '.implode(', ', $groupedTests));
        }
    }
}
