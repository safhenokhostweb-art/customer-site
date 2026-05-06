@extends('admin.layout')
@section('title', 'تبلیغات')
@section('header', 'تبلیغات')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <div class="admin-page-tools">
            <a href="{{ route('admin.advertisements.create') }}" class="btn btn-primary">تبلیغ جدید</a>
        </div>
        <form method="get" class="admin-page-tools">
            <select name="type" class="w-auto" onchange="this.form.submit()">
                <option value="">همه انواع</option>
                @foreach(\App\Models\Advertisement::typeLabels() as $k => $v)
                    <option value="{{ $k }}" {{ request('type') === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
            <select name="position" class="w-auto" onchange="this.form.submit()">
                <option value="">همه موقعیت‌ها</option>
                @foreach(\App\Models\Advertisement::positionLabels() as $k => $v)
                    <option value="{{ $k }}" {{ request('position') === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
            <thead>
                <tr>
                    <th>عنوان</th>
                    <th>نوع</th>
                    <th>موقعیت</th>
                    <th>ترتیب</th>
                    <th>وضعیت</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($advertisements as $ad)
                    <tr>
                        <td>{{ $ad->title ?: Str::limit($ad->content ?? '', 30) ?: '-' }}</td>
                        <td>{{ \App\Models\Advertisement::typeLabels()[$ad->type] ?? $ad->type }}</td>
                        <td>{{ \App\Models\Advertisement::positionLabels()[$ad->position] ?? $ad->position }}</td>
                        <td>{{ $ad->order }}</td>
                        <td>
                            @if($ad->is_active)
                                <span class="badge badge-success">فعال</span>
                            @else
                                <span class="badge badge-danger">غیرفعال</span>
                            @endif
                        </td>
                        <td class="flex gap-2">
                            <a href="{{ route('admin.advertisements.edit', $ad) }}" class="btn btn-primary btn-sm">ویرایش</a>
                            <form method="post" action="{{ route('admin.advertisements.destroy', $ad) }}" class="inline" onsubmit="return confirm('حذف این تبلیغ؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="admin-empty">هنوز تبلیغی ثبت نشده است. <a href="{{ route('admin.advertisements.create') }}" class="text-indigo-600 hover:underline">اولین تبلیغ را ایجاد کنید</a></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($advertisements->hasPages())
        <div class="admin-pagination">{{ $advertisements->links() }}</div>
    @endif
</div>
@endsection
