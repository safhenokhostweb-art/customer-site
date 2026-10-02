<?php

declare(strict_types=1);

require __DIR__.'/lib/ReleaseKnownIssues.php';
require __DIR__.'/../vendor/autoload.php';

try {
    $root = dirname(__DIR__);
    $read = static fn (string $file): array => json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    $descriptor = $read($root.'/.deployment/releases/canvas-homepage.json');
    $registry = $read($root.'/.deployment/known-issues.canvas-homepage.json');
    $group = 'canvas-homepage-baseline-2026-10-02';
    $registered = [];
    $groupReader = new PHPUnit\Metadata\Api\Groups();
    foreach (glob($root.'/tests/Feature/ThemeCanvas/*Test.php') as $file) {
        $class = 'Tests\\Feature\\ThemeCanvas\\'.basename($file, '.php');
        $reflection = new ReflectionClass($class);
        foreach (PHPUnit\Metadata\Parser\Registry::parser()->forClass($class)->isGroup() as $metadata) {
            if ($metadata->groupName() === $group) {
                throw new RuntimeException('Baseline approval cannot exclude a whole test class.');
            }
        }
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Use PHPUnit's own reader so legacy @group annotations cannot
            // silently exclude additional methods outside the approved list.
            if (in_array($group, $groupReader->groups($class, $method->getName(), false), true)) {
                $registered[] = $class.'::'.$method->getName();
            }
        }
    }
    $tests = ReleaseKnownIssues::validate($descriptor, $registry, $root, $registered, new DateTimeImmutable('now'));
    echo json_encode(['status'=>'verified', 'release_version'=>$descriptor['version'], 'known_baseline_tests'=>$tests], JSON_UNESCAPED_SLASHES).PHP_EOL;
    if (in_array('--run-tests', $argv, true)) {
        $process = proc_open([PHP_BINARY, $root.'/vendor/bin/phpunit', 'tests/Feature/ThemeCanvas', '--exclude-group', $group, '--do-not-cache-result', '--log-junit', 'storage/logs/release-canvas-results.xml'], [0=>STDIN, 1=>STDOUT, 2=>STDERR], $pipes, $root);
        if (! is_resource($process)) {
            throw new RuntimeException('Could not start the required ThemeCanvas suite.');
        }
        exit(proc_close($process));
    }
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage().PHP_EOL);
    exit(1);
}
