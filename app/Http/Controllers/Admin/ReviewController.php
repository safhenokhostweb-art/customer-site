<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with('reviewable', 'user')->orderBy('created_at', 'desc');

        if ($request->filled('approved')) {
            $query->where('is_approved', $request->boolean('approved'));
        }
        if ($request->filled('rating')) {
            $query->where('rating', $request->integer('rating'));
        }

        $reviews = $query->paginate(15)->withQueryString();

        $stats = [
            'total'    => Review::count(),
            'pending'  => Review::where('is_approved', false)->count(),
            'approved' => Review::where('is_approved', true)->count(),
            'avg'      => round(Review::avg('rating'), 1),
        ];

        return view('admin.reviews.index', compact('reviews', 'stats'));
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
