<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$phpBinary = PHP_BINARY;
$includeDirs = [
    $root . DIRECTORY_SEPARATOR . 'app',
    $root . DIRECTORY_SEPARATOR . 'bootstrap',
    $root . DIRECTORY_SEPARATOR . 'config',
    $root . DIRECTORY_SEPARATOR . 'database',
    $root . DIRECTORY_SEPARATOR . 'routes',
    $root . DIRECTORY_SEPARATOR . 'tests',
];

$files = [];
foreach ($includeDirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        /** @var SplFileInfo $file */
        if ($file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
}

sort($files);

if (empty($files)) {
    fwrite(STDOUT, "No PHP files found for linting.\n");
    exit(0);
}

$failures = [];
foreach ($files as $file) {
    $cmd = escapeshellarg($phpBinary) . ' -l ' . escapeshellarg($file) . ' 2>&1';
    exec($cmd, $output, $exitCode);
    if ($exitCode !== 0) {
        $failures[] = [
            'file' => $file,
            'output' => implode(PHP_EOL, $output),
        ];
    }
}

if (!empty($failures)) {
    fwrite(STDERR, "PHP lint failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, "- {$failure['file']}\n{$failure['output']}\n");
    }
    exit(1);
}

fwrite(STDOUT, "PHP lint passed for " . count($files) . " files.\n");
exit(0);
