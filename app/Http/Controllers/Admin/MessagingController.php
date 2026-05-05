<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessagingChannel;
use App\Models\Setting;
use Illuminate\Http\Request;

class MessagingController extends Controller
{
    public function index()
    {
        $channels = MessagingChannel::orderBy('order')->get();
        $platforms = MessagingChannel::platforms();
        $widgetLogo = Setting::get('messaging_widget_logo', '');
        return view('admin.messaging.index', compact('channels', 'platforms', 'widgetLogo'));
    }

    public function store(Request $request)
    {
        $platforms = array_keys(MessagingChannel::platforms());
        $data = $request->validate([
            'platform' => 'required|string|in:' . implode(',', $platforms),
            'url' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'message' => 'nullable|string|max:500',
            'custom_image' => 'nullable|string|max:500',
            'custom_label' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['order'] = MessagingChannel::max('order') + 1;
        if ($data['platform'] === 'whatsapp') {
            $data['phone'] = $data['phone'] ?? null;
            $data['message'] = $data['message'] ?? null;
        } else {
            $data['url'] = $data['url'] ?? null;
        }
        $data['custom_image'] = $data['custom_image'] ?? null;
        if ($data['platform'] === 'custom') {
            $data['custom_label'] = $data['custom_label'] ?? null;
        }
        MessagingChannel::create($data);
        return back()->with('success', 'کانال اضافه شد.');
    }

    public function edit(MessagingChannel $channel)
    {
        $platforms = MessagingChannel::platforms();
        return view('admin.messaging.edit', compact('channel', 'platforms'));
    }

    public function update(Request $request, MessagingChannel $channel)
    {
        $data = $request->validate([
            'url' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'message' => 'nullable|string|max:500',
            'custom_image' => 'nullable|string|max:500',
            'custom_label' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        if ($channel->platform === 'whatsapp') {
            $data['phone'] = $data['phone'] ?? null;
            $data['message'] = $data['message'] ?? null;
        } else {
            $data['url'] = $data['url'] ?? null;
        }
        $data['custom_image'] = $data['custom_image'] ?? null;
        if ($channel->platform === 'custom') {
            $data['custom_label'] = $data['custom_label'] ?? null;
        }
        $channel->update($data);
        return back()->with('success', 'کانال به‌روزرسانی شد.');
    }

    public function updateWidgetLogo(Request $request)
    {
        $request->validate(['widget_logo' => 'nullable|string|max:500']);
        Setting::set('messaging_widget_logo', $request->input('widget_logo', ''));
        return back()->with('success', 'لوگوی ویجت ذخیره شد.');
    }

    public function destroy(MessagingChannel $channel)
    {
        $channel->delete();
        return back()->with('success', 'کانال حذف شد.');
    }
}
