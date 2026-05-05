<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with('reviewable')->orderBy('created_at', 'desc');
        if ($request->filled('approved')) {
            $query->where('is_approved', $request->boolean('approved'));
        }
        $reviews = $query->paginate(15);
        return view('admin.reviews.index', compact('reviews'));
    }

    public function approve(Review $review)
    {
        $review->update(['is_approved' => true]);
        return back()->with('success', 'نظر تایید شد.');
    }

    public function reject(Review $review)
    {
        $review->update(['is_approved' => false]);
        return back()->with('success', 'نظر رد شد.');
    }

    public function destroy(Review $review)
    {
        $review->delete();
        return back()->with('success', 'نظر حذف شد.');
    }
}
