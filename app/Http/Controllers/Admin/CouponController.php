<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::orderBy('created_at', 'desc')->paginate(15);
        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        $products = \App\Models\Product::orderBy('title')->get(['id', 'title']);
        $categories = \App\Models\ProductCategory::orderBy('order')->get(['id', 'title']);
        return view('admin.coupons.create', compact('products', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validateCoupon($request, null);
        $data['code'] = strtoupper(trim($data['code']));
        Coupon::create($data);
        return redirect()->route('admin.coupons.index')->with('success', 'کوپن ذخیره شد.');
    }

    public function edit(Coupon $coupon)
    {
        $products = \App\Models\Product::orderBy('title')->get(['id', 'title']);
        $categories = \App\Models\ProductCategory::orderBy('order')->get(['id', 'title']);
        return view('admin.coupons.edit', compact('coupon', 'products', 'categories'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $data = $this->validateCoupon($request, $coupon->id);
        $data['code'] = strtoupper(trim($data['code']));
        $coupon->update($data);
        return redirect()->route('admin.coupons.index')->with('success', 'کوپن به‌روزرسانی شد.');
    }

    protected function validateCoupon(Request $request, ?int $couponId = null): array
    {
        $rules = [
            'code' => 'required|string|max:50|unique:coupons,code' . ($couponId ? ',' . $couponId : ''),
            'discount_type' => 'required|in:percent,fixed',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'discount_amount' => 'nullable|integer|min:0',
            'min_order_amount' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|string',
            'expires_at' => 'nullable|string',
            'usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'first_order_only' => 'nullable|boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:product_categories,id',
        ];
        if (($request->input('discount_type') ?: 'percent') === Coupon::TYPE_PERCENT) {
            $rules['discount_percent'] = 'required|integer|min:1|max:100';
        } else {
            $rules['discount_amount'] = 'required|integer|min:1';
        }
        $data = $request->validate($rules);
        $data['min_order_amount'] = !empty($data['min_order_amount']) ? (int) $data['min_order_amount'] : null;
        if (($data['discount_type'] ?? 'percent') === Coupon::TYPE_PERCENT) {
            $data['discount_percent'] = (int) ($data['discount_percent'] ?? 0);
            $data['discount_amount'] = null;
        } else {
            $data['discount_amount'] = (int) ($data['discount_amount'] ?? 0);
            $data['discount_percent'] = null;
        }
        foreach (['starts_at', 'expires_at'] as $key) {
            $data[$key] = !empty($data[$key]) ? \App\Helpers\Jalali::toCarbon($data[$key]) : null;
        }
        $data['is_active'] = $request->boolean('is_active');
        $data['first_order_only'] = $request->boolean('first_order_only');
        $data['product_ids'] = array_values(array_map('intval', $data['product_ids'] ?? []));
        $data['category_ids'] = array_values(array_map('intval', $data['category_ids'] ?? []));
        return $data;
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return redirect()->route('admin.coupons.index')->with('success', 'کوپن حذف شد.');
    }
}
