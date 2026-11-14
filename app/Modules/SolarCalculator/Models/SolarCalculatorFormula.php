<?php

namespace App\Modules\SolarCalculator\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A named constant or formula evaluated by the calculation engine.
 * `is_system` rows are required by the calculator itself: their
 * expression is editable, the row is not deletable.
 */
class SolarCalculatorFormula extends Model
{
    public const GROUPS = [
        'constants' => 'ثابت‌ها و ضرایب فنی',
        'prices' => 'قیمت پایهٔ تجهیزات',
        'sizing' => 'طراحی و ظرفیت سیستم',
        'pricing' => 'محاسبهٔ قیمت',
        'custom' => 'فرمول‌های سفارشی',
    ];

    protected $table = 'solar_calculator_formulas';

    protected $fillable = ['key', 'label', 'group', 'expression', 'unit', 'description', 'is_system', 'sort_order'];

    protected $casts = [
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function groupLabel(): string
    {
        return self::GROUPS[$this->group] ?? $this->group;
    }

    /** A constant is an expression that is just a number. */
    public function isConstant(): bool
    {
        return is_numeric(trim($this->expression));
    }
}
