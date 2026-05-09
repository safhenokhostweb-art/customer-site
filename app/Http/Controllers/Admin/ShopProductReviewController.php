<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\Request;

class ShopProductReviewController extends Controller
{
    public function index(Request $request)
    {
        $q = ProductReview::query()->with(['product', 'user'])->latest();
        if ($request->query('status') === 'pending') {
            $q->where('approved', false);
        } elseif ($request->query('status') === 'approved') {
            $q->where('approved', true);
        }
        $reviews = $q->paginate(20)->withQueryString();

        return view('admin.shop-product-reviews.index', compact('reviews'));
    }

    public function update(Request $request, ProductReview $productReview)
    {
        $data = $request->validate([
            'approved' => 'required|boolean',
        ]);
        $productReview->update($data);

        return redirect()->route('admin.shop-product-reviews.index')->with('success', 'نظر به‌روزرسانی شد.');
    }

    public function destroy(ProductReview $productReview)
    {
        $productReview->delete();

        return redirect()->route('admin.shop-product-reviews.index')->with('success', 'نظر حذف شد.');
    }
}
