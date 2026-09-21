<?php

use App\Models\License;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$database = (string) config('database.connections.mysql.database');
$key = (string) getenv('THEME_ASSIGNMENT_LICENSE_KEY');
if ($database !== 'customer_site_browser' || app()->environment('production') || strlen($key) < 12) {
    fwrite(STDERR, "Refusing real Platform license sync for database [{$database}].\n");
    exit(1);
}

$license = License::create([
    'license_key' => $key,
    'domain' => '127.0.0.1',
    'platform_valid' => true,
    'expires_at' => now()->addYear(),
    'last_validated_at' => now(),
    'modules' => [],
]);

echo 'CUSTOMER_LICENSE_SYNCED:' . $license->id . PHP_EOL;
