<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessCard;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BusinessCardController extends Controller
{
    public function index()
    {
        $cards = BusinessCard::orderBy('order')->paginate(15);
        return view('admin.business-cards.index', compact('cards'));
    }

    public function create()
    {
        return view('admin.business-cards.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'website' => 'nullable|url|max:500',
            'address' => 'nullable|string|max:500',
            'avatar' => 'nullable|string|max:500',
            'slug' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);
        $data['slug'] = !empty($data['slug'] ?? null) ? $data['slug'] : Str::slug($data['name']) . '-' . substr(uniqid(), -4);
        $data['is_active'] = $request->boolean('is_active');
        $data['order'] = $data['order'] ?? BusinessCard::max('order') + 1;
        BusinessCard::create($data);
        return redirect()->route('admin.business-cards.index')->with('success', 'کارت ویزیت ذخیره شد.');
    }

    public function edit(BusinessCard $businessCard)
    {
        return view('admin.business-cards.edit', compact('businessCard'));
    }

    public function update(Request $request, BusinessCard $businessCard)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'website' => 'nullable|url|max:500',
            'address' => 'nullable|string|max:500',
            'avatar' => 'nullable|string|max:500',
            'slug' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);
        $data['slug'] = $data['slug'] ?: $businessCard->slug;
        $data['is_active'] = $request->boolean('is_active');
        $businessCard->update($data);
        return redirect()->route('admin.business-cards.index')->with('success', 'کارت ویزیت به‌روزرسانی شد.');
    }

    public function destroy(BusinessCard $businessCard)
    {
        $businessCard->delete();
        return redirect()->route('admin.business-cards.index')->with('success', 'کارت ویزیت حذف شد.');
    }
}
