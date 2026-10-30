<?php

declare(strict_types=1);

// The canvas-homepage known-issue exemptions are retired; the guard must
// accept a clean tree and fail closed on every way of re-introducing one.

require __DIR__.'/../../scripts/lib/ReleaseKnownIssues.php';

$root = sys_get_temp_dir().'/canvas-known-issues-'.bin2hex(random_bytes(6));
mkdir($root.'/.deployment', 0700, true);
$passed = 0;
$rejects = static function (string $name, array $tests, array $classes, string $expected) use ($root, &$passed): void {
    try {
        ReleaseKnownIssues::assertNoExemptions($root, $tests, $classes);
    } catch (RuntimeException $exception) {
        if (! str_contains($exception->getMessage(), $expected)) {
            throw new RuntimeException("{$name}: unexpected rejection: ".$exception->getMessage());
        }
        echo 'PASS '.$name.PHP_EOL;
        $passed++;
        return;
    }
    throw new RuntimeException('Expected fail-closed rejection: '.$name);
};
try {
    ReleaseKnownIssues::assertNoExemptions($root, [], []);
    echo "PASS clean tree has no exemptions\n";
    $passed++;
    $rejects('grouped test method', ['Tests\\Feature\\ThemeCanvas\\FixtureTest::test_case'], [], 'exemptions are retired');
    $rejects('grouped test class', [], ['Tests\\Feature\\ThemeCanvas\\FixtureTest'], 'cannot exclude a whole test class');
    file_put_contents($root.'/'.ReleaseKnownIssues::RETIRED_REGISTRY, '{}');
    $rejects('re-introduced registry', [], [], 'must not exist');
    echo $passed." passed, 0 failed\n";
} finally {
    @unlink($root.'/'.ReleaseKnownIssues::RETIRED_REGISTRY);
    rmdir($root.'/.deployment');
    rmdir($root);
}
