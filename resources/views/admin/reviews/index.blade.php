@extends('admin.layout')
@section('title', 'نظرات و امتیازدهی')
@section('header', 'نظرات و امتیازدهی')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <p class="text-slate-600 text-sm">مدیریت نظرات و امتیازهای ثبت‌شده برای مطالب و محصولات.</p>
        <form method="get" class="admin-page-tools">
            <select name="approved" onchange="this.form.submit()" class="w-auto min-w-35">
                <option value="">همه نظرات</option>
                <option value="1" {{ request('approved') === '1' ? 'selected' : '' }}>تایید شده</option>
                <option value="0" {{ request('approved') === '0' ? 'selected' : '' }}>در انتظار تایید</option>
            </select>
        </form>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
            <thead>
                <tr>
                    <th>منبع</th>
                    <th>امتیاز</th>
                    <th>متن</th>
                    <th>وضعیت</th>
                    <th style="width:160px">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $r)
                    <tr>
                        <td><span class="text-slate-600">{{ class_basename($r->reviewable_type) }} #{{ $r->reviewable_id }}</span></td>
                        <td>
                            <span class="inline-flex items-center gap-1">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-4 h-4 {{ $i <= $r->rating ? 'text-amber-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                @endfor
                                <span class="text-sm font-medium mr-1">{{ $r->rating }}/5</span>
                            </span>
                        </td>
                        <td class="max-w-xs truncate">{{ Str::limit($r->body, 60) ?: '—' }}</td>
                        <td>
                            @if($r->is_approved)
                                <span class="badge badge-success">تایید شده</span>
                            @else
                                <span class="badge badge-warning">در انتظار</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                @if(!$r->is_approved)
                                    <form method="post" action="{{ route('admin.reviews.approve', $r) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm">تایید</button>
                                    </form>
                                @endif
                                <form method="post" action="{{ route('admin.reviews.destroy', $r) }}" class="inline" onsubmit="return confirm('حذف این نظر؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="admin-empty">هنوز نظری ثبت نشده است.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($reviews->hasPages())
        <div class="admin-pagination">{{ $reviews->links() }}</div>
    @endif
</div>
@endsection
