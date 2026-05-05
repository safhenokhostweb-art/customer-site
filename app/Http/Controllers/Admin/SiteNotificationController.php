<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Jalali;
use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\SiteNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteNotificationController extends Controller
{
    /**
     * لیست اعلان‌های تولبار سایت با فیلتر و خلاصه وضعیت.
     */
    public function index(Request $request): View
    {
        [$panelVisible, $canManage] = $this->resolveAccessFlags();
        if (! $panelVisible) {
            abort(403, 'ماژول اعلان‌ها برای این سایت فعال نیست.');
        }

        $query = SiteNotification::query()->orderByDesc('order')->orderByDesc('id');

        if ($request->filled('q')) {
            $q = '%' . trim($request->q) . '%';
            $query->where(function ($qb) use ($q) {
                $qb->where('title', 'like', $q)
                    ->orWhere('body', 'like', $q);
            });
        }

        if ($request->filled('status') && in_array($request->status, ['active', 'inactive'], true)) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('from')) {
            $from = Jalali::toCarbon($request->from);
            if ($from) {
                $query->whereDate('starts_at', '>=', $from->format('Y-m-d'));
            }
        }

        if ($request->filled('to')) {
            $to = Jalali::toCarbon($request->to);
            if ($to) {
                $query->whereDate('expires_at', '<=', $to->format('Y-m-d'));
            }
        }

        $sort = $request->get('sort', 'latest');
        if ($sort === 'oldest') {
            $query->reorder()->orderBy('order')->orderBy('id');
        }

        $notifications = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => SiteNotification::count(),
            'active' => SiteNotification::active()->count(),
        ];

        $license = License::current();

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'stats' => $stats,
            'canManage' => $canManage,
            'license' => $license,
        ]);
    }

    public function create(): View|RedirectResponse
    {
        $this->ensureCanManage();

        return view('admin.notifications.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManage();

        $data = $this->validatePayload($request);

        SiteNotification::create($data);

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', 'اعلان با موفقیت ایجاد شد.');
    }

    public function edit(SiteNotification $site_notification): View|RedirectResponse
    {
        $this->ensureCanManage();

        return view('admin.notifications.edit', compact('site_notification'));
    }

    public function update(Request $request, SiteNotification $site_notification): RedirectResponse
    {
        $this->ensureCanManage();

        $data = $this->validatePayload($request);

        $site_notification->update($data);

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', 'اعلان با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(SiteNotification $site_notification): RedirectResponse
    {
        $this->ensureCanManage();

        $site_notification->delete();

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', 'اعلان حذف شد. می‌توانید در صورت نیاز اعلان جدیدی با تنظیمات به‌روز ایجاد کنید.');
    }

    /**
     * تغییر سریع وضعیت فعال/غیرفعال.
     */
    public function toggle(SiteNotification $site_notification): RedirectResponse
    {
        $this->ensureCanManage();

        $site_notification->is_active = ! $site_notification->is_active;
        $site_notification->save();

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', 'وضعیت اعلان به‌روزرسانی شد.');
    }

    /**
     * اکشن گروهی برای فعال/غیرفعال کردن اعلان‌ها.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $this->ensureCanManage();

        $data = $request->validate([
            'action' => 'required|in:activate,deactivate',
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:notifications,id',
        ]);

        $ids = $data['ids'];
        $active = $data['action'] === 'activate';

        $count = SiteNotification::whereIn('id', $ids)->update(['is_active' => $active]);

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', "{$count} اعلان با موفقیت به‌روزرسانی شد.");
    }

    /**
     * اعتبارسنجی ورودی و تبدیل تاریخ‌های شمسی به Carbon.
     */
    protected function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'type' => 'required|string|in:info,success,warning,danger',
            'link' => 'nullable|string|max:500',
            'link_text' => 'nullable|string|max:100',
            'position' => 'required|string|in:top,left,right',
            'image_position' => 'required|string|in:background,left,right',
            'image_path' => 'nullable|string|max:500',
            'starts_at' => 'nullable|string|max:20',
            'expires_at' => 'nullable|string|max:20',
            'show_shaking_logo' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        $payload = [
            'title' => $validated['title'],
            'body' => $validated['body'] ?? null,
            'type' => $validated['type'],
            'link' => $validated['link'] ?? null,
            'link_text' => $validated['link_text'] ?? null,
            'position' => $validated['position'],
            'image_position' => $validated['image_position'],
            'image_path' => $validated['image_path'] ?? null,
            'show_shaking_logo' => isset($validated['show_shaking_logo']) ? (bool) $validated['show_shaking_logo'] : false,
            'is_active' => isset($validated['is_active']) ? (bool) $validated['is_active'] : false,
        ];

        if (! empty($validated['starts_at'])) {
            $from = Jalali::toCarbon($validated['starts_at']);
            $payload['starts_at'] = $from?->startOfDay();
        } else {
            $payload['starts_at'] = null;
        }

        if (! empty($validated['expires_at'])) {
            $to = Jalali::toCarbon($validated['expires_at']);
            $payload['expires_at'] = $to?->endOfDay();
        } else {
            $payload['expires_at'] = null;
        }

        return $payload;
    }

    /**
     * تعیین وضعیت نمایش و امکان مدیریت ماژول اعلان‌ها.
     *
     * @return array{0: bool, 1: bool} [panelVisible, canManage]
     */
    protected function resolveAccessFlags(): array
    {
        if (SiteHelper::isOwnerSite()) {
            return [true, true];
        }

        $license = License::current();
        if (! $license || ! is_array($license->modules)) {
            return [false, false];
        }

        $hasModule = in_array('notifications', $license->modules, true);
        if (! $hasModule) {
            return [false, false];
        }

        $canManage = $license->isValid();

        return [true, $canManage];
    }

    protected function ensureCanManage(): void
    {
        [$panelVisible, $canManage] = $this->resolveAccessFlags();

        if (! $panelVisible) {
            abort(403, 'ماژول اعلان‌ها برای این سایت فعال نیست.');
        }

        if (! $canManage) {
            abort(403, 'لایسنس ماژول اعلان‌ها منقضی شده یا معتبر نیست؛ امکان ویرایش وجود ندارد.');
        }
    }
}

