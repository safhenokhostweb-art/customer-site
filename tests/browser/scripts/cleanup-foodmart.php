<?php

use App\Models\License;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$connection = (string) config('database.default');
$database = (string) config("database.connections.{$connection}.database");
if (!str_contains($database, 'browser')) {
    throw new RuntimeException("Refusing FoodMart browser-fixture cleanup outside a browser database: {$database}");
}

Product::query()->where('slug', 'like', 'browser-%')->delete();
ProductCategory::query()->where('slug', 'like', 'browser-%')->delete();
License::query()->where('license_key', 'BROWSER-FOODMART-BINDING')->delete();

$raw = Setting::get('active_canvas_theme_data');
$theme = is_string($raw) ? json_decode($raw, true) : null;
if (is_array($theme) && is_array($theme['pages'] ?? null)) {
    unset($theme['pages']['foodmart-binding']);
    Setting::set('active_canvas_theme_data', json_encode($theme, JSON_UNESCAPED_UNICODE), 'theme');
}

fwrite(STDOUT, "FoodMart browser fixtures removed from {$database}.\n");
