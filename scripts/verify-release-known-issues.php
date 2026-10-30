<?php

declare(strict_types=1);

require __DIR__.'/lib/ReleaseKnownIssues.php';
require __DIR__.'/../vendor/autoload.php';

try {
    $root = dirname(__DIR__);
    $group = ReleaseKnownIssues::RETIRED_GROUP;
    $groupedTests = [];
    $groupedClasses = [];
    $groupReader = new PHPUnit\Metadata\Api\Groups();
    foreach (glob($root.'/tests/Feature/ThemeCanvas/*Test.php') as $file) {
        $class = 'Tests\\Feature\\ThemeCanvas\\'.basename($file, '.php');
        $reflection = new ReflectionClass($class);
        foreach (PHPUnit\Metadata\Parser\Registry::parser()->forClass($class)->isGroup() as $metadata) {
            if ($metadata->groupName() === $group) {
                $groupedClasses[] = $class;
            }
        }
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // PHPUnit's own reader, so legacy @group annotations are caught too.
            if (in_array($group, $groupReader->groups($class, $method->getName(), false), true)) {
                $groupedTests[] = $class.'::'.$method->getName();
            }
        }
    }
    ReleaseKnownIssues::assertNoExemptions($root, $groupedTests, $groupedClasses);
    echo json_encode(['status'=>'verified', 'known_issue_exemptions'=>[]], JSON_UNESCAPED_SLASHES).PHP_EOL;
    if (in_array('--run-tests', $argv, true)) {
        // Release verification owns a dedicated database, so a concurrent
        // development suite on customer_site_test can neither invalidate it
        // nor be broken by it. phpunit.xml does not force DB_DATABASE, so the
        // process environment wins. Only *_release_test names are accepted.
        $database = getenv('RELEASE_TEST_DB_DATABASE') ?: 'customer_site_release_test';
        if (preg_match('/^[A-Za-z0-9_]+_release_test$/D', $database) !== 1) {
            throw new RuntimeException("Release test database must be a dedicated *_release_test schema, got '{$database}'.");
        }
        fwrite(STDERR, "release test database: {$database}".PHP_EOL);
        $process = proc_open([PHP_BINARY, $root.'/vendor/bin/phpunit', 'tests/Feature/ThemeCanvas', '--do-not-cache-result', '--log-junit', 'storage/logs/release-canvas-results.xml'], [0=>STDIN, 1=>STDOUT, 2=>STDERR], $pipes, $root, ['DB_DATABASE' => $database] + getenv());
        if (! is_resource($process)) {
            throw new RuntimeException('Could not start the required ThemeCanvas suite.');
        }
        exit(proc_close($process));
    }
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage().PHP_EOL);
    exit(1);
}
