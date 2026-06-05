<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscriptionPlanController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::withCount(['subscriptions', 'activeSubscriptions'])
            ->orderBy('order')
            ->paginate(20);

        return view('admin.subscriptions.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.subscriptions.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'slug'             => 'nullable|string|max:255',
            'description'      => 'nullable|string',
            'price'            => 'required|numeric|min:0',
            'special_price'    => 'nullable|numeric|min:0',
            'discount_code'    => 'nullable|string|max:50',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'duration'         => 'required|in:month,year',
            'duration_count'   => 'required|integer|min:1',
            'trial_days'       => 'nullable|integer|min:0',
            'features'         => 'nullable|array',
            'features.*'       => 'nullable|string|max:200',
            'color'            => 'nullable|string|max:20',
            'highlighted'      => 'nullable|boolean',
            'badge_text'       => 'nullable|string|max:50',
            'max_users'        => 'nullable|integer|min:1',
            'is_active'        => 'nullable|boolean',
            'order'            => 'nullable|integer|min:0',
            'image'            => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $data['slug']        = ($data['slug'] ?? null) ?: Str::slug($data['name']);
        $data['is_active']   = $request->boolean('is_active');
        $data['highlighted'] = $request->boolean('highlighted');
        $data['trial_days']  = $data['trial_days'] ?? 0;
        $data['order']       = $data['order'] ?? (SubscriptionPlan::max('order') + 1);

        $features = array_values(array_filter($data['features'] ?? [], fn($f) => filled($f)));
        $data['features'] = count($features) ? $features : null;

        $imgFile = $data['image'] ?? null;
        unset($data['image']);
        if (self::fileExistsOnDisk($imgFile)) {
            $data['image'] = $imgFile->store('subscriptions', 'public');
        }

        SubscriptionPlan::create($data);

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'پلن اشتراک با موفقیت ذخیره شد.');
    }

    public function edit(SubscriptionPlan $subscription)
    {
        return view('admin.subscriptions.edit', compact('subscription'));
    }

    public function update(Request $request, SubscriptionPlan $subscription)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'slug'             => 'nullable|string|max:255',
            'description'      => 'nullable|string',
            'price'            => 'required|numeric|min:0',
            'special_price'    => 'nullable|numeric|min:0',
            'discount_code'    => 'nullable|string|max:50',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'duration'         => 'required|in:month,year',
            'duration_count'   => 'required|integer|min:1',
            'trial_days'       => 'nullable|integer|min:0',
            'features'         => 'nullable|array',
            'features.*'       => 'nullable|string|max:200',
            'color'            => 'nullable|string|max:20',
            'highlighted'      => 'nullable|boolean',
            'badge_text'       => 'nullable|string|max:50',
            'max_users'        => 'nullable|integer|min:1',
            'is_active'        => 'nullable|boolean',
            'order'            => 'nullable|integer|min:0',
            'image'            => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $data['slug']        = ($data['slug'] ?? null) ?: Str::slug($data['name']);
        $data['is_active']   = $request->boolean('is_active');
        $data['highlighted'] = $request->boolean('highlighted');
        $data['trial_days']  = $data['trial_days'] ?? 0;

        $features = array_values(array_filter($data['features'] ?? [], fn($f) => filled($f)));
        $data['features'] = count($features) ? $features : null;

        // پس از validation، image یا null است یا یک UploadedFile معتبر
        $imgFile = $data['image'] ?? null;
        unset($data['image']);

        if ($imgFile instanceof \Illuminate\Http\UploadedFile) {
            if ($subscription->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($subscription->image);
            }
            $data['image'] = $imgFile->store('subscriptions', 'public');
        }

        if ($request->boolean('remove_image') && $subscription->image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($subscription->image);
            $data['image'] = null;
        }

        $subscription->update($data);

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'پلن اشتراک به‌روزرسانی شد.');
    }

    public function destroy(SubscriptionPlan $subscription)
    {
        if ($subscription->activeSubscriptions()->exists()) {
            return redirect()->route('admin.subscriptions.index')
                ->with('error', 'این پلن دارای مشترک فعال است و قابل حذف نیست.');
        }

        if ($subscription->image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($subscription->image);
        }

        $subscription->delete();

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'پلن حذف شد.');
    }
}
