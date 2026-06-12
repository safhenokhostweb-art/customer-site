@extends('admin.layout')
@section('title', 'پلن‌های اشتراک')
@section('header', 'پلن‌های اشتراک')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <p class="text-slate-600 text-sm">مدیریت پلن‌های عضویت و اشتراک کاربران.</p>
        <div class="admin-page-tools">
            <a href="{{ route('admin.subscriptions.dashboard') }}" class="btn btn-secondary btn-sm">
                <x-core-ui::icon name="dashboard" class="w-4 h-4" />
                داشبورد
            </a>
            <a href="{{ route('admin.subscribers.index') }}" class="btn btn-secondary btn-sm">
                <x-core-ui::icon name="users" class="w-4 h-4" />
                مشترکین
            </a>
            <a href="{{ route('admin.subscriptions.create') }}" class="btn btn-primary">
                <x-core-ui::icon name="plus" class="w-4 h-4" />
                پلن جدید
            </a>
        </div>
    </div>

    
    

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>نام پلن</th>
                        <th>قیمت</th>
                        <th>مدت</th>
                        <th>آزمایشی</th>
                        <th>مشترک کل / فعال</th>
                        <th>وضعیت</th>
                        <th style="width:200px">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $p)
                    <tr>
                        <td>
                            <div class="flex items-center gap-2">
                                @if($p->color)
                                <span class="w-3 h-3 rounded-full shrink-0" style="background:{{ $p->color }}"></span>
                                @endif
                                <span class="font-medium">{{ $p->name }}</span>
                                @if($p->highlighted)
                                <span class="badge badge-info text-xs">پیشنهادی</span>
                                @endif
                            </div>
                        </td>
                        <td class="font-semibold">{{ \App\Helpers\NumberHelper::formatFa($p->price) }} تومان</td>
                        <td>{{ $p->getDurationLabel() }}</td>
                        <td>
                            @if($p->hasTrial())
                            <span class="badge badge-info">{{ \App\Helpers\NumberHelper::formatFa($p->trial_days) }} روز</span>
                            @else
                            <span class="text-gray-400 text-xs">ندارد</span>
                            @endif
                        </td>
                        <td>
                            <span class="font-medium">{{ \App\Helpers\NumberHelper::formatFa($p->subscriptions_count) }}</span>
                            / <span class="text-green-600 font-medium">{{ \App\Helpers\NumberHelper::formatFa($p->active_subscriptions_count) }}</span>
                        </td>
                        <td>
                            @if($p->is_active)
                            <span class="badge badge-success">فعال</span>
                            @else
                            <span class="badge badge-danger">غیرفعال</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.subscriptions.edit', $p) }}" class="btn btn-primary btn-sm">ویرایش</a>
                                <a href="{{ route('admin.subscribers.index', ['plan_id' => $p->id]) }}" class="btn btn-secondary btn-sm">مشترکین</a>
                                <form method="post" action="{{ route('admin.subscriptions.destroy', $p) }}"
                                      class="inline" onsubmit="return confirm('آیا این پلن حذف شود؟');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="admin-empty">
                            هنوز پلنی ثبت نشده.
                            <a href="{{ route('admin.subscriptions.create') }}" class="text-indigo-600 hover:underline">اولین پلن را بسازید</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($plans->hasPages())
    <div class="admin-pagination">{{ $plans->links() }}</div>
    @endif
</div>
@endsection
