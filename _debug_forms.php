<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "base_path: " . base_path() . PHP_EOL;
echo "public_path: " . public_path() . PHP_EOL;
echo "storage_path: " . storage_path() . PHP_EOL;
echo "APP_URL: " . config('app.url') . PHP_EOL;
echo PHP_EOL;

// Check view namespace
$exists = view()->exists('forms::admin.forms.index');
echo "forms::admin.forms.index exists: " . ($exists ? 'YES' : 'NO') . PHP_EOL;

// Check provider loaded
$providers = app()->getLoadedProviders();
$formsProvider = 'SaaSPlatform\Modules\Forms\Providers\FormsServiceProvider';
echo "FormsServiceProvider loaded: " . (isset($providers[$formsProvider]) && $providers[$formsProvider] ? 'YES' : 'NO') . PHP_EOL;

// List all view namespaces
echo PHP_EOL . "All view namespaces:" . PHP_EOL;
$factory = app('view');
$hints = $factory->getFinder()->getHints();
foreach ($hints as $namespace => $paths) {
    echo "  $namespace: " . implode(', ', $paths) . PHP_EOL;
}
