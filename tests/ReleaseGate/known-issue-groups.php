<?php

declare(strict_types=1);

// Exercise PHPUnit's real metadata reader, including legacy @group syntax.
$root = dirname(__DIR__, 2);
$class = 'KnownIssueGuardFixture'.bin2hex(random_bytes(5)).'Test';
$file = $root.'/tests/Feature/ThemeCanvas/'.$class.'.php';
$group = 'canvas-homepage-baseline-2026-10-02';
$cases = [
    ['method docblock', '', '/** @group '.$group.' */', 'Registered baseline groups must match'],
    ['method attribute', '', '#[\\PHPUnit\\Framework\\Attributes\\Group(\''.$group.'\')]', 'Registered baseline groups must match'],
    ['class docblock', '/** @group '.$group.' */', '', 'cannot exclude a whole test class'],
    ['class attribute', '#[\\PHPUnit\\Framework\\Attributes\\Group(\''.$group.'\')]', '', 'cannot exclude a whole test class'],
];
try {
    foreach ($cases as [$name, $classMetadata, $methodMetadata, $expected]) {
        $code = "<?php\nnamespace Tests\\Feature\\ThemeCanvas;\n".$classMetadata."\nclass ".$class." extends \\PHPUnit\\Framework\\TestCase {\n".$methodMetadata."\npublic function test_unapproved(): void {}\n}\n";
        file_put_contents($file, $code);
        $process = proc_open([PHP_BINARY, $root.'/scripts/verify-release-known-issues.php'], [1=>['pipe','w'],2=>['pipe','w']], $pipes, $root);
        if (! is_resource($process)) {
            throw new RuntimeException('Could not start registry verification.');
        }
        $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        if ($exit === 0 || ! str_contains($output, $expected)) {
            throw new RuntimeException('Unknown exclusion was not rejected ('.$name.'): '.$output);
        }
        echo 'PASS unknown '.$name.' group rejected'.PHP_EOL;
    }
    echo "4 passed, 0 failed\n";
} finally {
    if (is_file($file)) {
        unlink($file);
    }
}
