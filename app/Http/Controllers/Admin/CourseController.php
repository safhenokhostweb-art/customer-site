<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::withCount(['classes', 'enrollments'])
            ->orderBy('order')
            ->paginate(15);

        $stats = [
            'total'       => Course::count(),
            'active'      => Course::where('is_active', true)->count(),
            'free'        => Course::where('price', 0)->count(),
            'enrollments' => CourseEnrollment::count(),
        ];

        return view('admin.courses.index', compact('courses', 'stats'));
    }

    public function create()
    {
        return view('admin.courses.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);
        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['title']);
        $data['is_active'] = $request->boolean('is_active');
        $data['order'] = $data['order'] ?? Course::max('order') + 1;
        Course::create($data);
        return redirect()->route('admin.courses.index')->with('success', 'دوره ذخیره شد.');
    }

    public function edit(Course $course)
    {
        $course->loadCount(['classes', 'enrollments']);
        return view('admin.courses.edit', compact('course'));
    }

    public function update(Request $request, Course $course)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);
        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['title']);
        $data['is_active'] = $request->boolean('is_active');
        $course->update($data);
        return redirect()->route('admin.courses.index')->with('success', 'دوره به‌روزرسانی شد.');
    }

    public function destroy(Course $course)
    {
        $course->delete();
        return redirect()->route('admin.courses.index')->with('success', 'دوره حذف شد.');
    }
}
