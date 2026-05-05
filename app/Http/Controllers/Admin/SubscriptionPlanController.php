<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscriptionPlanController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::orderBy('order')->paginate(15);
        return view('admin.subscriptions.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.subscriptions.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|in:month,year',
            'duration_count' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);
        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');
        $data['order'] = $data['order'] ?? SubscriptionPlan::max('order') + 1;
        SubscriptionPlan::create($data);
        return redirect()->route('admin.subscriptions.index')->with('success', 'پلن ذخیره شد.');
    }

    public function edit(SubscriptionPlan $subscription)
    {
        return view('admin.subscriptions.edit', compact('subscription'));
    }

    public function update(Request $request, SubscriptionPlan $subscription)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|in:month,year',
            'duration_count' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);
        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');
        $subscription->update($data);
        return redirect()->route('admin.subscriptions.index')->with('success', 'پلن به‌روزرسانی شد.');
    }

    public function destroy(SubscriptionPlan $subscription)
    {
        $subscription->delete();
        return redirect()->route('admin.subscriptions.index')->with('success', 'پلن حذف شد.');
    }
}
