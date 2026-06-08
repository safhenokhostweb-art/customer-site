<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function index()
    {
        $endpoints = WebhookEndpoint::withCount('webhookEvents')
            ->orderByDesc('created_at')
            ->paginate(15);
        return view('admin.webhooks.index', compact('endpoints'));
    }

    public function create()
    {
        $availableEvents = WebhookEndpoint::EVENTS;
        return view('admin.webhooks.create', compact('availableEvents'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'url'         => 'required|url|max:500',
            'description' => 'nullable|string|max:255',
            'events'      => 'required|array|min:1',
            'events.*'    => 'string|in:' . implode(',', array_keys(WebhookEndpoint::EVENTS)),
            'is_active'   => 'nullable|boolean',
        ]);

        WebhookEndpoint::create([
            'url'         => $data['url'],
            'description' => $data['description'] ?? null,
            'events'      => $data['events'],
            'secret'      => Str::random(40),
            'is_active'   => isset($data['is_active']),
        ]);

        return redirect()->route('admin.webhooks.index')->with('success', 'Webhook endpoint ایجاد شد.');
    }

    public function show(WebhookEndpoint $webhook)
    {
        $events = WebhookEvent::where('webhook_endpoint_id', $webhook->id)
            ->orderByDesc('created_at')
            ->paginate(20);
        return view('admin.webhooks.show', compact('webhook', 'events'));
    }

    public function edit(WebhookEndpoint $webhook)
    {
        $availableEvents = WebhookEndpoint::EVENTS;
        return view('admin.webhooks.edit', compact('webhook', 'availableEvents'));
    }

    public function update(Request $request, WebhookEndpoint $webhook)
    {
        $data = $request->validate([
            'url'         => 'required|url|max:500',
            'description' => 'nullable|string|max:255',
            'events'      => 'required|array|min:1',
            'events.*'    => 'string|in:' . implode(',', array_keys(WebhookEndpoint::EVENTS)),
            'is_active'   => 'nullable|boolean',
        ]);

        $webhook->update([
            'url'         => $data['url'],
            'description' => $data['description'] ?? null,
            'events'      => $data['events'],
            'is_active'   => isset($data['is_active']),
        ]);

        return redirect()->route('admin.webhooks.show', $webhook)->with('success', 'Webhook endpoint آپدیت شد.');
    }

    public function rotateSecret(WebhookEndpoint $webhook)
    {
        $webhook->update(['secret' => Str::random(40)]);
        return back()->with('success', 'Secret جدید تولید شد.');
    }

    public function destroy(WebhookEndpoint $webhook)
    {
        $webhook->delete();
        return redirect()->route('admin.webhooks.index')->with('success', 'Webhook endpoint حذف شد.');
    }
}
