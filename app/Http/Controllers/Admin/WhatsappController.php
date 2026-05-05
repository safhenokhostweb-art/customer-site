<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class WhatsappController extends Controller
{
    public function index()
    {
        $phone = Setting::get('whatsapp_phone', '');
        $message = Setting::get('whatsapp_message', '');
        $enabled = (bool) Setting::get('whatsapp_enabled', false);
        return view('admin.whatsapp.index', compact('phone', 'message', 'enabled'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'whatsapp_phone' => 'nullable|string|max:50',
            'whatsapp_message' => 'nullable|string|max:500',
            'whatsapp_enabled' => 'nullable|boolean',
        ]);
        Setting::set('whatsapp_phone', $request->whatsapp_phone ?? '');
        Setting::set('whatsapp_message', $request->whatsapp_message ?? '');
        Setting::set('whatsapp_enabled', $request->boolean('whatsapp_enabled') ? '1' : '0');
        return back()->with('success', 'تنظیمات واتساپ ذخیره شد.');
    }
}
