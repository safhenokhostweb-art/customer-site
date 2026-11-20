<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile as SfUploadedFile;

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
        $allProducts = \App\Models\Product::where('status', 'published')
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('admin.subscriptions.create', compact('allProducts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                    => 'required|string|max:255',
            'slug'                    => 'nullable|string|max:255',
            'description'             => 'nullable|string',
            'price'                   => 'required|numeric|min:0',
            'special_price'           => 'nullable|numeric|min:0',
            'discount_code'           => 'nullable|string|max:50',
            'duration'                => 'required|in:day,month,year',
            'duration_count'          => 'required|integer|min:1',
            'trial_days'              => 'nullable|integer|min:0',
            'features'                => 'nullable|array',
            'features.*'              => 'nullable|string|max:200',
            'color'                   => 'nullable|string|max:20',
            'highlighted'             => 'nullable|boolean',
            'badge_text'              => 'nullable|string|max:50',
            'max_users'               => 'nullable|integer|min:1',
            'is_active'               => 'nullable|boolean',
            'order'                   => 'nullable|integer|min:0',
            'product_discount_percent'  => 'nullable|integer|min:0|max:100',
            'apply_to_all_products'     => 'nullable|boolean',
            'coupon_override'           => 'nullable|boolean',
            'free_shipping'             => 'nullable|boolean',
            'vip_support'               => 'nullable|boolean',
            'early_access'              => 'nullable|boolean',
            'loyalty_multiplier'        => 'nullable|integer|min:1|max:10',
        ]);

        $data['slug']                    = ($data['slug'] ?? null) ?: Str::slug($data['name']);
        $data['is_active']               = $request->boolean('is_active');
        $data['highlighted']             = $request->boolean('highlighted');
        $data['apply_to_all_products']   = $request->boolean('apply_to_all_products');
        $data['coupon_override']         = $request->boolean('coupon_override');
        $data['free_shipping']           = $request->boolean('free_shipping');
        $data['vip_support']             = $request->boolean('vip_support');
        $data['early_access']            = $request->boolean('early_access');
        $data['trial_days']              = $data['trial_days'] ?? 0;
        $data['loyalty_multiplier']      = $data['loyalty_multiplier'] ?? 1;
        $data['product_discount_percent'] = $data['product_discount_percent'] ?? 0;
        $data['order']                   = $data['order'] ?? (SubscriptionPlan::max('order') + 1);

        $features = array_values(array_filter($data['features'] ?? [], fn($f) => filled($f)));
        $data['features'] = count($features) ? $features : null;

        $data['image'] = $this->storeImage($request);

        SubscriptionPlan::create($data);

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'پلن اشتراک با موفقیت ذخیره شد.');
    }

    public function edit(SubscriptionPlan $subscription)
    {
        $subscription->load('products');
        $allProducts = \App\Models\Product::where('status', 'published')
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('admin.subscriptions.edit', compact('subscription', 'allProducts'));
    }

    public function update(Request $request, SubscriptionPlan $subscription)
    {
        $data = $request->validate([
            'name'                    => 'required|string|max:255',
            'slug'                    => 'nullable|string|max:255',
            'description'             => 'nullable|string',
            'price'                   => 'required|numeric|min:0',
            'special_price'           => 'nullable|numeric|min:0',
            'discount_code'           => 'nullable|string|max:50',
            'duration'                => 'required|in:day,month,year',
            'duration_count'          => 'required|integer|min:1',
            'trial_days'              => 'nullable|integer|min:0',
            'features'                => 'nullable|array',
            'features.*'              => 'nullable|string|max:200',
            'color'                   => 'nullable|string|max:20',
            'highlighted'             => 'nullable|boolean',
            'badge_text'              => 'nullable|string|max:50',
            'max_users'               => 'nullable|integer|min:1',
            'is_active'               => 'nullable|boolean',
            'order'                   => 'nullable|integer|min:0',
            'product_discount_percent'  => 'nullable|integer|min:0|max:100',
            'apply_to_all_products'     => 'nullable|boolean',
            'coupon_override'           => 'nullable|boolean',
            'free_shipping'             => 'nullable|boolean',
            'vip_support'               => 'nullable|boolean',
            'early_access'              => 'nullable|boolean',
            'loyalty_multiplier'        => 'nullable|integer|min:1|max:10',
            'product_ids'               => 'nullable|array',
            'product_ids.*'             => 'exists:products,id',
            'product_discounts'         => 'nullable|array',
            'product_discounts.*'       => 'nullable|integer|min:0|max:100',
            'product_coupon_overrides'  => 'nullable|array',
        ]);

        $data['slug']                    = ($data['slug'] ?? null) ?: Str::slug($data['name']);
        $data['is_active']               = $request->boolean('is_active');
        $data['highlighted']             = $request->boolean('highlighted');
        $data['apply_to_all_products']   = $request->boolean('apply_to_all_products');
        $data['coupon_override']         = $request->boolean('coupon_override');
        $data['free_shipping']           = $request->boolean('free_shipping');
        $data['vip_support']             = $request->boolean('vip_support');
        $data['early_access']            = $request->boolean('early_access');
        $data['trial_days']              = $data['trial_days'] ?? 0;
        $data['loyalty_multiplier']      = $data['loyalty_multiplier'] ?? 1;
        $data['product_discount_percent'] = $data['product_discount_percent'] ?? 0;

        $features = array_values(array_filter($data['features'] ?? [], fn($f) => filled($f)));
        $data['features'] = count($features) ? $features : null;

        // حذف تصویر
        if ($request->boolean('remove_image') && $subscription->image) {
            $subscription->deleteStoredImage();
            $data['image'] = null;
        }

        // آپلود تصویر جدید
        $newImage = $this->storeImage($request);
        if ($newImage !== null) {
            if ($subscription->image) {
                $subscription->deleteStoredImage();
            }
            $data['image'] = $newImage;
        }

        // حذف فیلدهای pivot از $data
        $productIds             = $data['product_ids'] ?? [];
        $productDiscounts       = $data['product_discounts'] ?? [];
        $productCouponOverrides = $data['product_coupon_overrides'] ?? [];
        unset($data['product_ids'], $data['product_discounts'], $data['product_coupon_overrides']);

        $subscription->update($data);

        // sync محصولات مرتبط
        $syncData = [];
        foreach ($productIds as $productId) {
            $syncData[$productId] = [
                'discount_percent'         => (int) ($productDiscounts[$productId] ?? $subscription->product_discount_percent),
                'override_coupon_exclusion' => isset($productCouponOverrides[$productId]),
            ];
        }
        $subscription->products()->sync($syncData);

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
            $subscription->deleteStoredImage();
        }

        $subscription->delete();

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'پلن حذف شد.');
    }

    /**
     * آپلود تصویر پلن — مقاوم در برابر empty submit مرورگرها.
     * اگر فایل معتبری آپلود نشده: null برمی‌گردد.
     */
    private function storeImage(Request $request): ?string
    {
        // از request->file() استفاده کن که همیشه Illuminate\Http\UploadedFile برمی‌گرداند
        $file = $request->file('image');

        if (! $file instanceof SfUploadedFile) {
            return null;
        }

        // UPLOAD_ERR_OK = 0، هر مقدار دیگر یعنی آپلود نشد یا خالی است
        if ($file->getError() !== UPLOAD_ERR_OK) {
            return null;
        }

        // اندازه صفر = فایلی انتخاب نشده (Firefox edge case)
        if ($file->getSize() <= 0) {
            return null;
        }

        // نام اصلی خالی = فایلی انتخاب نشده
        if (! filled($file->getClientOriginalName())) {
            return null;
        }

        // Media disk (MEDIA_DISK), under SubscriptionPlan::IMAGE_PREFIX so the
        // model can tell these from older paths on the local "public" disk.
        app(\App\Services\MediaQuota::class)->assertCanStore((int) $file->getSize(), 'image');

        try {
            $extension = strtolower($file->guessExtension() ?: ($file->getClientOriginalExtension() ?: 'jpg'));
            $path = SubscriptionPlan::IMAGE_PREFIX . Str::random(40) . '.' . $extension;
            $stream = fopen($file->getRealPath() ?: $file->getPathname(), 'rb');
            try {
                $ok = $stream !== false && app(\App\Services\MediaStorage::class)->disk('uploads')->put($path, $stream, ['visibility' => 'public']);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
            return $ok ? $path : null;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('SubscriptionPlan image upload failed', [
                'error'    => $e->getMessage(),
                'tmp_name' => $file->getPathname(),
            ]);
            return null;
        }
    }
}
