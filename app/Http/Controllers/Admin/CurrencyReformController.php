<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\CurrencyHelper;
use App\Models\Setting;
use Illuminate\Http\Request;

class CurrencyReformController extends Controller
{
    public function index()
    {
        $available = CurrencyHelper::isModuleAvailable();
        $toggleEnabled = CurrencyHelper::isToggleEnabled();
        $enabled = CurrencyHelper::isReformEnabled();

        return view('admin.currency-reform.index', compact('available', 'toggleEnabled', 'enabled'));
    }

    public function update(Request $request)
    {
        if (!CurrencyHelper::isModuleAvailable()) {
            return back()->with('error', 'ماژول در دسترس نیست.');
        }
        $value = $request->has('currency_reform_enabled') ? '1' : '0';
        Setting::set('currency_reform_enabled', $value, 'currency');
        return back()->with('success', 'تنظیمات ذخیره شد.');
    }
}
