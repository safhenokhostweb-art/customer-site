<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationBatch;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    /**
     * صفحهٔ اصلی اعلان‌ها: ارسال به کاربران.
     */
    public function sendToUsersForm()
    {
        $userCount = User::count();
        $users = User::orderBy('name')->get(['id', 'name', 'email', 'mobile', 'created_at']);
        return view('admin.notifications.send-users', compact('userCount', 'users'));
    }

    /**
     * ارسال اعلان به کاربران (همه / انتخاب‌شده / بازهٔ تاریخ ثبت‌نام).
     */
    public function sendToUsers(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'type' => 'required|in:info,success,warning,danger,promo,ad',
            'link' => 'nullable|string|max:500',
            'cta_text' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048',
            'recipient_type' => 'required|in:all,selected,date_range',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
            'registered_from' => 'nullable|string|max:20',
            'registered_to' => 'nullable|string|max:20',
        ]);

        $users = $this->resolveRecipients($data);
        if ($users->isEmpty()) {
            return back()->withInput()->with('error', 'هیچ کاربری برای ارسال انتخاب نشده است.');
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $dir = 'notifications/' . date('Y/m');
            $path = $file->storeAs($dir, Str::random(20) . '.' . $file->getClientOriginalExtension(), 'uploads');
            $imageUrl = asset('uploads/' . $path);
        }

        $dataPayload = array_filter([
            'image_url' => $imageUrl,
            'cta_text' => $data['cta_text'] ?? null,
            'style' => in_array($data['type'], ['promo', 'ad'], true) ? $data['type'] : 'info',
        ]);

        $batch = NotificationBatch::create([
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
            'type' => $data['type'],
            'link' => $data['link'] ?? null,
            'data' => $dataPayload ?: null,
            'sent_at' => now(),
            'recipient_count' => $users->count(),
            'created_by' => auth()->id(),
        ]);

        foreach ($users as $user) {
            UserNotification::create([
                'user_id' => $user->id,
                'notification_batch_id' => $batch->id,
                'title' => $data['title'],
                'body' => $data['body'] ?? null,
                'type' => $data['type'],
                'link' => $data['link'] ?? null,
                'data' => $dataPayload ?: null,
            ]);
        }

        return redirect()
            ->route('admin.notifications.send-users.form')
            ->with('success', "اعلان برای {$users->count()} کاربر ارسال شد.");
    }

    protected function resolveRecipients(array $data)
    {
        if ($data['recipient_type'] === 'all') {
            return User::select('id')->get();
        }
        if ($data['recipient_type'] === 'selected' && !empty($data['user_ids'])) {
            return User::whereIn('id', $data['user_ids'])->select('id')->get();
        }
        if ($data['recipient_type'] === 'date_range') {
            $query = User::select('id');
            if (!empty($data['registered_from'])) {
                $from = \App\Helpers\Jalali::toCarbon($data['registered_from']);
                if ($from) {
                    $query->whereDate('created_at', '>=', $from->format('Y-m-d'));
                }
            }
            if (!empty($data['registered_to'])) {
                $to = \App\Helpers\Jalali::toCarbon($data['registered_to']);
                if ($to) {
                    $query->whereDate('created_at', '<=', $to->format('Y-m-d'));
                }
            }
            return $query->get();
        }
        return collect();
    }

    /**
     * آرشیو اعلان‌ها با جستجو و فیلتر تاریخ.
     */
    public function archive(Request $request)
    {
        $query = NotificationBatch::with('createdByUser')->latest('sent_at');

        if ($request->filled('q')) {
            $q = '%' . $request->q . '%';
            $query->where(function ($qb) use ($q) {
                $qb->where('title', 'like', $q)->orWhere('body', 'like', $q);
            });
        }
        if ($request->filled('from')) {
            $from = \App\Helpers\Jalali::toCarbon($request->from);
            if ($from) {
                $query->whereDate('sent_at', '>=', $from->format('Y-m-d'));
            }
        }
        if ($request->filled('to')) {
            $to = \App\Helpers\Jalali::toCarbon($request->to);
            if ($to) {
                $query->whereDate('sent_at', '<=', $to->format('Y-m-d'));
            }
        }

        $batches = $query->paginate(20)->withQueryString();
        return view('admin.notifications.archive', compact('batches'));
    }
}
