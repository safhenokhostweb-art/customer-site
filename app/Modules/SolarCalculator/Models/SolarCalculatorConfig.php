<?php

namespace App\Modules\SolarCalculator\Models;

use Illuminate\Database\Eloquent\Model;

/** One settings section (e.g. `ui`, `company`, `access`) stored as JSON. */
class SolarCalculatorConfig extends Model
{
    protected $table = 'solar_calculator_configs';

    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'array'];
}
