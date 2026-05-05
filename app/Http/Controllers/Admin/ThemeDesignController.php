<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ThemeSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThemeDesignController extends Controller
{
    public function index(): View
    {
        $settings = ThemeSetting::current();
        $buttonStyles = ThemeSetting::buttonStyles();
        return view('admin.theme-design.index', compact('settings', 'buttonStyles'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'primary_color' => 'nullable|string|max:32',
            'secondary_color' => 'nullable|string|max:32',
            'font_family_heading' => 'nullable|string|max:128',
            'font_family_body' => 'nullable|string|max:128',
            'base_font_size' => 'nullable|string|max:24',
            'container_width' => 'nullable|string|max:32',
            'border_radius' => 'nullable|string|max:32',
            'button_style' => 'nullable|in:solid,outline,rounded',
            'section_spacing' => 'nullable|in:xs,sm,md,lg,xl',
        ]);
        $settings = ThemeSetting::firstOrNew(['site_id' => null]);
        $settings->fill($data);
        $settings->site_id = null;
        $settings->save();
        ThemeSetting::clearCache();
        return back()->with('success', 'تنظیمات ظاهر ذخیره شد.');
    }
}
