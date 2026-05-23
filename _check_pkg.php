<?php
$installed = json_decode(file_get_contents(__DIR__ . '/vendor/composer/installed.json'), true);

$packages = $installed['packages'] ?? $installed;
echo "Format: " . (isset($installed['packages']) ? 'new (packages key)' : 'old (flat)') . PHP_EOL;
echo "Total packages: " . count($packages) . PHP_EOL;
echo PHP_EOL;

$found = false;
foreach ($packages as $pkg) {
    $name = $pkg['name'] ?? 'unknown';
    if (strpos($name, 'saas') !== false || strpos($name, 'forms') !== false || strpos($name, 'tickets') !== false) {
        echo "FOUND: $name" . PHP_EOL;
        echo "  type: " . ($pkg['type'] ?? 'N/A') . PHP_EOL;
        echo "  extra: " . (isset($pkg['extra']['laravel']['providers']) ? json_encode($pkg['extra']['laravel']['providers']) : 'NONE') . PHP_EOL;
        echo "  install-path: " . ($pkg['install-path'] ?? 'N/A') . PHP_EOL;
        echo PHP_EOL;
        $found = true;
    }
}

if (!$found) {
    echo "NO saas-platform packages found in installed.json!" . PHP_EOL;
    echo PHP_EOL;
    echo "Checking vendor/saas-platform directory..." . PHP_EOL;
    $dir = __DIR__ . '/vendor/saas-platform';
    if (is_dir($dir)) {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..') {
                echo "  - $item" . PHP_EOL;
                $pkgJson = "$dir/$item/composer.json";
                if (file_exists($pkgJson)) {
                    $pkgData = json_decode(file_get_contents($pkgJson), true);
                    echo "    name: " . ($pkgData['name'] ?? 'N/A') . PHP_EOL;
                    echo "    extra: " . (isset($pkgData['extra']['laravel']['providers']) ? json_encode($pkgData['extra']['laravel']['providers']) : 'NONE') . PHP_EOL;
                }
            }
        }
    } else {
        echo "  vendor/saas-platform directory does not exist!" . PHP_EOL;
    }
}
