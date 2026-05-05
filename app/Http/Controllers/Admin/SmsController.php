<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function index()
    {
        $provider = Setting::get('sms_provider', '');
        $apiKey = Setting::get('sms_api_key', '');
        $lineNumber = Setting::get('sms_line_number', '');
        return view('admin.sms.index', compact('provider', 'apiKey', 'lineNumber'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'sms_provider' => 'nullable|string|max:100',
            'sms_api_key' => 'nullable|string|max:255',
            'sms_line_number' => 'nullable|string|max:50',
        ]);
        Setting::set('sms_provider', $request->sms_provider ?? '');
        Setting::set('sms_api_key', $request->sms_api_key ?? '');
        Setting::set('sms_line_number', $request->sms_line_number ?? '');
        return back()->with('success', 'تنظیمات پیامک ذخیره شد.');
    }
}
