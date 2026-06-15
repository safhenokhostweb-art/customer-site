<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Helpers\SiteHelper;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\UserNotification;
use App\Services\MembershipPointsService;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();

        $ordersCount = 0;
        $ordersTotal = 0;
        $activeSubscriptionsCount = 0;
        $walletBalance = 0;
        $unreadNotificationsCount = 0;

        if (SiteHelper::shopEnabled()) {
            $ordersQuery = Order::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('customer_mobile', $user->mobile);
            });
            $ordersCount = $ordersQuery->count();
            $ordersTotal = (float) $ordersQuery->sum('total');
        }

        if (SiteHelper::moduleEnabled('subscription')) {
            $activeSubscriptionsCount = Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->count();
        }

        if (config('account.wallet_enabled', false)) {
            $walletBalance = (float) ($user->wallet_balance ?? 0);
        }

        $unreadNotificationsCount = UserNotification::where('user_id', $user->id)->unread()->count();

        $pointsBalance = app(MembershipPointsService::class)->getBalance($user);

        $recentOrders = SiteHelper::shopEnabled()
            ? Order::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('customer_mobile', $user->mobile);
            })->latest()->take(5)->get()
            : collect();

        $recentInvoices = SiteHelper::moduleEnabled('invoice')
            ? Invoice::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('customer_mobile', $user->mobile);
            })->latest()->take(5)->get()
            : collect();

        $recentNotifications = UserNotification::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // دوره‌های ثبت‌نام شده
        $enrolledCourses = collect();
        $enrolledCoursesCount = 0;
        if (SiteHelper::moduleEnabled('courses') && Schema::hasTable('course_enrollments')) {
            $enrolledCourses = \App\Models\CourseEnrollment::where('user_id', $user->id)
                ->with(['course.classes'])
                ->latest()
                ->take(4)
                ->get()
                ->map(function ($enrollment) use ($user) {
                    $course = $enrollment->course;
                    if (!$course) { return $enrollment; }
                    $totalClasses = $course->classes?->count() ?? 0;
                    if ($totalClasses > 0 && Schema::hasTable('course_class_completions')) {
                        $completed = \App\Models\CourseClassCompletion::where('user_id', $user->id)
                            ->whereIn('course_class_id', $course->classes->pluck('id'))
                            ->count();
                        $enrollment->progress_percentage = (int) round($completed / $totalClasses * 100);
                    } else {
                        $enrollment->progress_percentage = 0;
                    }
                    return $enrollment;
                });
            $enrolledCoursesCount = \App\Models\CourseEnrollment::where('user_id', $user->id)->count();
        }

        // اقساط فعال
        $activeInstallments = collect();
        if (SiteHelper::moduleEnabled('installments') && Schema::hasTable('installment_orders')) {
            $activeInstallments = \App\Models\InstallmentOrder::where('user_id', $user->id)
                ->whereIn('status', ['active', 'pending'])
                ->with('plan')
                ->latest()
                ->take(3)
                ->get();
        }

        return view('account.dashboard', compact(
            'ordersCount',
            'ordersTotal',
            'activeSubscriptionsCount',
            'walletBalance',
            'unreadNotificationsCount',
            'recentOrders',
            'recentInvoices',
            'recentNotifications',
            'pointsBalance',
            'enrolledCourses',
            'enrolledCoursesCount',
            'activeInstallments'
        ));
    }
}
