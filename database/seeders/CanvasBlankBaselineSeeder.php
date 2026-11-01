<?php

namespace Database\Seeders;

use App\Services\ThemeCanvas\CanvasCleanSlate;
use Illuminate\Database\Seeder;

/**
 * Installs the empty canvas as the active theme on a site that has none.
 * Idempotent: an existing active theme is never replaced.
 *
 *   php artisan db:seed --class=CanvasBlankBaselineSeeder
 */
class CanvasBlankBaselineSeeder extends Seeder
{
    public function run(CanvasCleanSlate $cleanSlate): void
    {
        $cleanSlate->installBaseline();
    }
}
