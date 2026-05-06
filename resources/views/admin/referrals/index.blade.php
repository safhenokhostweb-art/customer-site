@extends('admin.layout')
@section('title', 'معرفی دوستان')
@section('header', 'معرفی دوستان')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <p class="text-slate-600 text-sm">کدهای معرفی و ردیابی معرفی‌های انجام‌شده توسط کاربران.</p>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
            <thead>
                <tr>
                    <th>کد</th>
                    <th>کاربر</th>
                    <th>تعداد استفاده</th>
                    <th>وضعیت</th>
                </tr>
            </thead>
            <tbody>
                @forelse($codes as $c)
                    <tr>
                        <td><code class="px-2 py-1 bg-slate-100 rounded text-sm font-mono">{{ $c->code }}</code></td>
                        <td>{{ $c->user?->name ?? $c->user?->email ?? '—' }}</td>
                        <td><span class="font-semibold">{{ $c->referrals->count() }}</span></td>
                        <td>
                            @if($c->is_active)
                                <span class="badge badge-success">فعال</span>
                            @else
                                <span class="badge badge-danger">غیرفعال</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="admin-empty">هنوز کد معرفی ثبت نشده است.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($codes->hasPages())
        <div class="admin-pagination">{{ $codes->links() }}</div>
    @endif
</div>
@endsection
