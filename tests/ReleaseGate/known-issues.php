<?php

declare(strict_types=1);

require __DIR__.'/../../scripts/lib/ReleaseKnownIssues.php';

$root = sys_get_temp_dir().'/canvas-known-issues-'.bin2hex(random_bytes(6));
$sourceFiles = [];
$directories = [];
foreach (ReleaseKnownIssues::SOURCE_FILES as $path) {
    $directory = dirname($root.'/'.$path);
    if (! is_dir($directory)) {
        mkdir($directory, 0700, true);
    }
    while ($directory !== $root) {
        $directories[$directory] = true;
        $directory = dirname($directory);
    }
    file_put_contents($root.'/'.$path, 'unchanged baseline');
    $sourceFiles[$path] = hash('sha256', 'unchanged baseline');
}
$file = $root.'/'.ReleaseKnownIssues::SOURCE_FILES[0];
$now = new DateTimeImmutable('2026-10-02T06:00:00Z');
$descriptor = ['version'=>'test-release', 'source_commits'=>['customer-site'=>str_repeat('a', 40)]];
$symbols = array_map(static fn (int $i): string => 'Tests\\Feature\\ThemeCanvas\\FixtureTest::test_case_'.$i, range(1, 4));
$registry = [
    'schema_version'=>1, 'release_version'=>'test-release', 'source_commits'=>$descriptor['source_commits'],
    'group'=>'canvas-homepage-baseline-2026-10-02', 'recorded_at'=>'2026-10-02T00:00:00Z', 'expires_at'=>'2026-10-16T00:00:00Z',
    'issues'=>array_map(static fn (string $symbol, int $i): array => ['id'=>'TEST-'.$i, 'test'=>$symbol, 'reason'=>'Existing baseline', 'owner'=>'Canvas'], $symbols, range(1, 4)),
    'fingerprint_format'=>'sha256-utf8-lf-v1', 'source_files'=>$sourceFiles,
];
$passed = 0;
$check = static function (string $name, array $candidate, array $registered = []) use ($root, $now, $descriptor, $symbols, &$passed): void {
    try {
        ReleaseKnownIssues::validate($descriptor, $candidate, $root, $registered ?: $symbols, $now);
    } catch (Throwable) {
        echo 'PASS '.$name.PHP_EOL;
        $passed++;
        return;
    }
    throw new RuntimeException('Expected fail-closed rejection: '.$name);
};
try {
    if (count(ReleaseKnownIssues::validate($descriptor, $registry, $root, $symbols, $now)) !== 4) {
        throw new RuntimeException('The exact approved registry did not validate.');
    }
    echo "PASS exact version and four named methods\n";
    $passed++;
    $r=$registry; $r['release_version']='another-release'; $check('wrong release version', $r);
    $r=$registry; $r['source_commits']['customer-site']=str_repeat('b',40); $check('wrong source commit', $r);
    $r=$registry; $r['expires_at']='2026-10-02T05:00:00Z'; $check('expired approval', $r);
    $r=$registry; $r['expires_at']='2026-11-01T00:00:00Z'; $check('overlong approval', $r);
    $check('unregistered test excluded by group', $registry, [...$symbols, 'Tests\\Feature\\ThemeCanvas\\OtherTest::test_unexpected']);
    $check('approved test missing from group', $registry, array_slice($symbols, 0, 3));
    $r=$registry; $r['issues'][1]['id']=$r['issues'][0]['id']; $check('duplicate issue identity', $r);
    $r=$registry; $r['issues'][0]['test']='Tests\\Feature\\ThemeCanvas\\*'; $check('wildcard method forbidden', $r);
    $r=$registry; $r['issues'][]=$r['issues'][0]; $check('additional exemptions forbidden', $r);
    $r=$registry; $r['source_files']=['resources/views/../../secret'=>str_repeat('a',64)]; $check('path traversal forbidden', $r);
    $r=$registry; $r['source_files']=[]; $check('missing source fingerprints', $r);
    $r=$registry; unset($r['source_files'][ReleaseKnownIssues::SOURCE_FILES[0]]); $check('one mandatory fingerprint missing', $r);
    $r=$registry; $r['source_files'][ReleaseKnownIssues::SOURCE_FILES[0]]=hash('sha256', "line one\nline two\n");
    file_put_contents($file, "line one\r\nline two\r\n");
    ReleaseKnownIssues::validate($descriptor, $r, $root, $symbols, $now);
    echo "PASS identical text across Windows/Linux line endings\n";
    $passed++;
    file_put_contents($file, 'changed source'); $check('source changes invalidate approval', $registry);
    echo $passed." passed, 0 failed\n";
} finally {
    foreach (ReleaseKnownIssues::SOURCE_FILES as $path) {
        unlink($root.'/'.$path);
    }
    $directories = array_keys($directories);
    usort($directories, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
    foreach ($directories as $directory) {
        rmdir($directory);
    }
    rmdir($root);
}
