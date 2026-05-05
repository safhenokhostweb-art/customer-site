@extends('admin.layout')
@section('title', 'شبکه‌های اجتماعی')
@section('header', 'لینک‌های شبکه‌های اجتماعی')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <p class="text-slate-600 text-sm">مدیریت لینک‌های شبکه‌های اجتماعی (خارجی و ایرانی) برای نمایش در سایت.</p>
    <a href="{{ route('admin.social-links.create') }}" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        لینک جدید
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>پلتفرم</th>
                    <th>آدرس</th>
                    <th>وضعیت</th>
                    <th style="width:180px">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($links as $l)
                    <tr>
                        <td class="font-medium">{{ \App\Models\SocialLink::platforms()[$l->platform] ?? $l->platform }}</td>
                        <td>
                            <a href="{{ $l->url }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline text-sm truncate max-w-[200px] block">
                                {{ Str::limit($l->url, 45) }}
                            </a>
                        </td>
                        <td>
                            @if($l->is_active)
                                <span class="badge badge-success">فعال</span>
                            @else
                                <span class="badge badge-danger">غیرفعال</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.social-links.edit', $l) }}" class="btn btn-primary btn-sm">ویرایش</a>
                                <form method="post" action="{{ route('admin.social-links.destroy', $l) }}" class="inline" onsubmit="return confirm('حذف این لینک؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-12 text-slate-500">هنوز لینکی ثبت نشده است. <a href="{{ route('admin.social-links.create') }}" class="text-indigo-600 hover:underline">اولین لینک را اضافه کنید</a></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($links->hasPages())
    <div class="mt-4">{{ $links->links() }}</div>
@endif
@endsection
