<?php

use App\Http\Controllers\Admin\ExchangeRateController;
use App\Modules\SolarCalculator\Http\Controllers\Admin\SolarCalculatorFormulaController;
use App\Modules\SolarCalculator\Http\Controllers\Admin\SolarCalculatorLeadController;
use App\Modules\SolarCalculator\Http\Controllers\Admin\SolarCalculatorSettingsController;
use App\Modules\SolarCalculator\Http\Controllers\SolarCalculatorController;
use Illuminate\Support\Facades\Route;

/*
| Solar calculator module routes (loaded by SolarCalculatorServiceProvider).
| Every route sits behind `module:solar_calculator`: on a site whose license
| does not include the module they answer 403, so the module stays
| invisible to every other tenant even though all installs ship the code.
*/

Route::middleware(['web', 'redirect.if.not.installed', 'module:solar_calculator'])->group(function () {
    Route::post('/solar-calculator/calculate', [SolarCalculatorController::class, 'calculate'])
        ->middleware('throttle:30,1')->name('solar-calculator.calculate');
    Route::post('/solar-calculator/quote', [SolarCalculatorController::class, 'storeQuote'])
        ->middleware('throttle:6,1')->name('solar-calculator.quote.store');
    Route::get('/solar-calculator/quote/{token}', [SolarCalculatorController::class, 'showQuote'])
        ->where('token', '[A-Za-z0-9]{48}')->middleware('throttle:60,1')->name('solar-calculator.quote.show');
});

Route::middleware([
    'web', 'redirect.if.not.installed', 'auth', 'admin', 'license.valid', 'permission:dashboard',
    'force.password.change', 'module:solar_calculator', 'permission:solar_calculator',
])->prefix('admin')->name('admin.')->group(function () {
    Route::prefix('solar-calculator')->name('solar-calculator.')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.solar-calculator.leads.index'))->name('index');

        Route::get('leads', [SolarCalculatorLeadController::class, 'index'])->name('leads.index');
        Route::get('leads/export', [SolarCalculatorLeadController::class, 'export'])->name('leads.export');
        Route::get('leads/{lead}', [SolarCalculatorLeadController::class, 'show'])->whereNumber('lead')->name('leads.show');
        Route::get('leads/{lead}/quote', [SolarCalculatorLeadController::class, 'quote'])->whereNumber('lead')->name('leads.quote');
        Route::put('leads/{lead}', [SolarCalculatorLeadController::class, 'update'])->whereNumber('lead')->name('leads.update');
        Route::delete('leads/{lead}', [SolarCalculatorLeadController::class, 'destroy'])->whereNumber('lead')->name('leads.destroy');

        Route::get('formulas', [SolarCalculatorFormulaController::class, 'index'])->name('formulas.index');
        Route::post('formulas', [SolarCalculatorFormulaController::class, 'store'])->name('formulas.store');
        Route::post('formulas/test', [SolarCalculatorFormulaController::class, 'test'])->name('formulas.test');
        Route::get('formulas/{formula}/edit', [SolarCalculatorFormulaController::class, 'edit'])->whereNumber('formula')->name('formulas.edit');
        Route::put('formulas/{formula}', [SolarCalculatorFormulaController::class, 'update'])->whereNumber('formula')->name('formulas.update');
        Route::post('formulas/{formula}/restore', [SolarCalculatorFormulaController::class, 'restore'])->whereNumber('formula')->name('formulas.restore');
        Route::delete('formulas/{formula}', [SolarCalculatorFormulaController::class, 'destroy'])->whereNumber('formula')->name('formulas.destroy');

        Route::get('settings', [SolarCalculatorSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SolarCalculatorSettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/reset', [SolarCalculatorSettingsController::class, 'reset'])->name('settings.reset');
    });

    // The exchange-rate store is generic (App\Services\ExchangeRates); its
    // admin page is gated by its only consumer today. Widen this gate when
    // another module starts reading rates.
    Route::prefix('exchange-rates')->name('exchange-rates.')->group(function () {
        Route::get('/', [ExchangeRateController::class, 'index'])->name('index');
        Route::put('rates', [ExchangeRateController::class, 'updateRates'])->name('rates.update');
        Route::put('provider', [ExchangeRateController::class, 'updateProvider'])->name('provider.update');
        Route::post('sync', [ExchangeRateController::class, 'sync'])->middleware('throttle:6,1')->name('sync');
    });
});
