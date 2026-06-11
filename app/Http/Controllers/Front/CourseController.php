<?php

namespace App\Http\Controllers\Front;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseClassCompletion;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        if (! SiteHelper::moduleEnabled('courses')) {
            abort(404);
        }

        $query = Course::active()->withCount('enrollments');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qb) use ($q) {
                $qb->where('title', 'like', "%{$q}%")
                   ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $filter = $request->input('filter', 'all');
        if ($filter === 'free') {
            $query->where('price', 0);
        } elseif ($filter === 'paid') {
            $query->where('price', '>', 0);
        }

        $courses = $query->orderBy('order')->paginate(12)->withQueryString();

        $enrolledIds = [];
        if (Auth::check()) {
            $enrolledIds = CourseEnrollment::where('user_id', Auth::id())
                ->pluck('course_id')
                ->toArray();
        }

        return view('themes.default.courses.index', compact('courses', 'enrolledIds', 'filter'));
    }

    public function show(string $slug)
    {
        if (! SiteHelper::moduleEnabled('courses')) {
            abort(404);
        }

        $course = Course::active()->where('slug', $slug)->with('classes')->firstOrFail();
        $course->loadCount('enrollments');

        $enrollment = null;
        $completedClassIds = [];
        if (Auth::check()) {
            $enrollment = CourseEnrollment::where('course_id', $course->id)
                ->where('user_id', Auth::id())
                ->first();
            $completedClassIds = CourseClassCompletion::where('course_id', $course->id)
                ->where('user_id', Auth::id())
                ->pluck('course_class_id')
                ->toArray();
        }

        $progress = $enrollment && Auth::check()
            ? $course->progressForUser(Auth::id())
            : 0;

        return view('themes.default.courses.show', compact(
            'course', 'enrollment', 'completedClassIds', 'progress'
        ));
    }

    public function enroll(Request $request, string $slug)
    {
        if (! SiteHelper::moduleEnabled('courses')) {
            abort(404);
        }

        $course = Course::active()->where('slug', $slug)->firstOrFail();

        $existing = CourseEnrollment::where('course_id', $course->id)
            ->where('user_id', Auth::id())
            ->exists();

        if ($existing) {
            return redirect()->route('courses.learn', $slug)
                ->with('info', 'شما قبلاً در این دوره ثبت‌نام کرده‌اید.');
        }

        if (! $course->isFree()) {
            return redirect()->route('courses.show', $slug)
                ->with('error', 'برای ثبت‌نام در این دوره باید هزینه را پرداخت کنید.');
        }

        CourseEnrollment::create([
            'course_id'   => $course->id,
            'user_id'     => Auth::id(),
            'status'      => 'active',
            'enrolled_at' => now(),
        ]);

        return redirect()->route('courses.learn', $slug)
            ->with('success', 'ثبت‌نام شما با موفقیت انجام شد. یادگیری را شروع کنید!');
    }

    public function myLearning()
    {
        if (! SiteHelper::moduleEnabled('courses')) {
            abort(404);
        }

        $enrollments = CourseEnrollment::where('user_id', Auth::id())
            ->with(['course' => fn ($q) => $q->withCount('classes')])
            ->latest()
            ->get();

        $progressMap = [];
        foreach ($enrollments as $en) {
            if ($en->course) {
                $progressMap[$en->course_id] = $en->course->progressForUser(Auth::id());
            }
        }

        return view('themes.default.courses.my-learning', compact('enrollments', 'progressMap'));
    }

    public function learn(string $slug, int $classOrder = 1)
    {
        if (! SiteHelper::moduleEnabled('courses')) {
            abort(404);
        }

        $course = Course::active()->where('slug', $slug)->with('classes')->firstOrFail();

        $enrollment = CourseEnrollment::where('course_id', $course->id)
            ->where('user_id', Auth::id())
            ->first();

        if (! $enrollment) {
            return redirect()->route('courses.show', $slug)
                ->with('error', 'ابتدا باید در این دوره ثبت‌نام کنید.');
        }

        $classes     = $course->classes;
        $currentClass = $classes->firstWhere('order', $classOrder) ?? $classes->first();

        if (! $currentClass) {
            return redirect()->route('courses.show', $slug)
                ->with('info', 'این دوره هنوز جلسه‌ای ندارد.');
        }

        $completedClassIds = CourseClassCompletion::where('course_id', $course->id)
            ->where('user_id', Auth::id())
            ->pluck('course_class_id')
            ->toArray();

        $progress = $course->progressForUser(Auth::id());

        $prevClass = $classes->firstWhere('order', $classOrder - 1);
        $nextClass = $classes->firstWhere('order', $classOrder + 1);

        return view('themes.default.courses.learn', compact(
            'course', 'currentClass', 'classes', 'completedClassIds',
            'progress', 'prevClass', 'nextClass'
        ));
    }

    public function markComplete(Request $request, string $slug, int $classId)
    {
        if (! SiteHelper::moduleEnabled('courses')) {
            abort(404);
        }

        $course = Course::active()->where('slug', $slug)->firstOrFail();

        $enrolled = CourseEnrollment::where('course_id', $course->id)
            ->where('user_id', Auth::id())
            ->exists();

        if (! $enrolled) {
            return response()->json(['error' => 'ثبت‌نام نشده‌اید'], 403);
        }

        $classExists = $course->classes()->where('id', $classId)->exists();
        if (! $classExists) {
            return response()->json(['error' => 'جلسه یافت نشد'], 404);
        }

        CourseClassCompletion::firstOrCreate([
            'course_class_id' => $classId,
            'user_id'         => Auth::id(),
            'course_id'       => $course->id,
        ], ['completed_at' => now()]);

        $progress = $course->progressForUser(Auth::id());

        if ($progress === 100) {
            CourseEnrollment::where('course_id', $course->id)
                ->where('user_id', Auth::id())
                ->update(['status' => 'completed']);
        }

        return response()->json(['progress' => $progress, 'completed' => true]);
    }
}
