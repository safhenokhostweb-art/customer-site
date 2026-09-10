@extends('admin.layout')

@section('title', 'کتابخانه رسانه')

@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">کتابخانه رسانه</h1>
            <p style="margin:6px 0 0;color:#6b7280;font-size:13px;">رسانه‌های این سایت؛ مستقل از سایر سایت‌ها</p>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px;padding:16px;">
        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
            @csrf
            <label style="display:grid;gap:6px;min-width:240px;flex:1;">
                <span style="font-size:13px;font-weight:600;">فایل رسانه</span>
                <input name="file" type="file" required accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,audio/mpeg,audio/ogg,audio/wav,application/pdf">
            </label>
            <label style="display:grid;gap:6px;min-width:180px;flex:1;">
                <span style="font-size:13px;font-weight:600;">عنوان (اختیاری)</span>
                <input name="title" value="{{ old('title') }}" maxlength="255" style="padding:9px;border:1px solid #d1d5db;border-radius:7px;">
            </label>
            <button class="btn btn-primary" type="submit">بارگذاری رسانه</button>
        </form>
        @error('file')<p style="margin:10px 0 0;color:#b91c1c;font-size:13px;">{{ $message }}</p>@enderror
    </div>

    <form method="GET" action="{{ route('admin.media.index') }}" class="card" style="padding:12px 16px;margin-bottom:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="جستجو در عنوان، نام فایل یا توضیح" style="min-width:240px;flex:1;padding:9px;border:1px solid #d1d5db;border-radius:7px;">
        <select name="category" style="padding:9px;border:1px solid #d1d5db;border-radius:7px;">
            <option value="">همه انواع</option>
            @foreach(['image' => 'تصویر', 'video' => 'ویدئو', 'audio' => 'صوت', 'document' => 'سند'] as $value => $label)
                <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary" type="submit">فیلتر</button>
    </form>

    <div class="media-grid">
        @forelse($items as $item)
            <article class="media-card">
                <a href="{{ route('admin.media.show', $item->uuid) }}" class="media-preview" aria-label="مشاهده {{ $item->title ?: $item->original_filename }}">
                    @if($item->category === 'image')
                        <img src="{{ $item->preview_url }}" alt="{{ $item->alt_text ?: $item->title ?: '' }}">
                    @elseif($item->category === 'video')
                        <video src="{{ $item->preview_url }}" preload="metadata" muted></video><span>ویدئو</span>
                    @elseif($item->category === 'audio')
                        <span>فایل صوتی</span>
                    @else
                        <span>PDF</span>
                    @endif
                </a>
                <div class="media-card-body">
                    <strong title="{{ $item->title ?: $item->original_filename }}">{{ \Illuminate\Support\Str::limit($item->title ?: $item->original_filename, 44) }}</strong>
                    <small>{{ $item->original_filename }}</small>
                    <div class="media-meta"><span>{{ ['image' => 'تصویر', 'video' => 'ویدئو', 'audio' => 'صوت', 'document' => 'سند'][$item->category] }}</span><span>{{ $item->created_at->format('Y/m/d') }}</span></div>
                    <div class="media-meta"><span class="usage {{ $item->usages_count ? 'usage-used' : '' }}">{{ $item->usages_count ? 'در حال استفاده' : 'بدون استفاده' }}</span><a href="{{ route('admin.media.show', $item->uuid) }}">جزئیات</a></div>
                </div>
            </article>
        @empty
            <div class="card admin-empty" style="grid-column:1/-1;">رسانه‌ای برای این سایت ثبت نشده است.</div>
        @endforelse
    </div>

    <div class="admin-pagination">{{ $items->links('admin.media._pagination') }}</div>
</div>

<style>
    .media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px}.media-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;box-shadow:0 1px 2px rgb(0 0 0 / .03)}.media-preview{height:150px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;color:#4b5563;text-decoration:none;overflow:hidden}.media-preview img,.media-preview video{width:100%;height:100%;object-fit:cover}.media-card-body{padding:12px;display:grid;gap:6px}.media-card-body strong{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.media-card-body small{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#6b7280}.media-meta{display:flex;justify-content:space-between;gap:8px;font-size:12px;color:#6b7280}.usage{color:#047857}.usage-used{color:#b45309}@media(max-width:390px){.media-grid{grid-template-columns:1fr}.media-preview{height:180px}}
</style>
@endsection
