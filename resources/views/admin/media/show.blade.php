@extends('admin.layout')

@section('title', 'جزئیات رسانه')

@section('content')
<div class="admin-page">
    <div class="admin-page-head"><h1 class="admin-page-title">جزئیات رسانه</h1><div class="admin-page-tools"><a class="btn btn-secondary" href="{{ route('admin.media.index') }}">بازگشت به کتابخانه</a></div></div>
    <div style="display:grid;grid-template-columns:minmax(0,1.2fr) minmax(280px,1fr);gap:16px;align-items:start;" class="media-detail-grid">
        <section class="card" style="padding:16px;">
            <div style="min-height:260px;background:#f3f4f6;border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                @if($item->category === 'image')
                    <img src="{{ $item->preview_url }}" alt="{{ $item->alt_text ?: $item->title ?: '' }}" style="max-width:100%;max-height:520px;object-fit:contain;">
                @elseif($item->category === 'video')
                    <video controls preload="metadata" src="{{ $item->preview_url }}" style="max-width:100%;max-height:520px;"></video>
                @elseif($item->category === 'audio')
                    <audio controls src="{{ $item->preview_url }}"></audio>
                @else
                    <a class="btn btn-primary" href="{{ $item->preview_url }}" target="_blank" rel="noopener noreferrer">باز کردن PDF</a>
                @endif
            </div>
            <dl style="margin:16px 0 0;display:grid;grid-template-columns:auto 1fr;gap:8px 14px;font-size:13px;"><dt>نام فایل</dt><dd>{{ $item->original_filename }}</dd><dt>نوع</dt><dd>{{ $item->mime_type }}</dd><dt>اندازه</dt><dd>{{ number_format($item->size_bytes / 1024, 1) }} KB</dd><dt>تاریخ</dt><dd>{{ $item->created_at->format('Y/m/d H:i') }}</dd></dl>
        </section>
        <section class="card" style="padding:16px;">
            <form method="POST" action="{{ route('admin.media.update', $item->uuid) }}" style="display:grid;gap:12px;">@csrf @method('PATCH')
                <label>عنوان<input name="title" value="{{ old('title', $item->title) }}" maxlength="255" style="width:100%;margin-top:5px;padding:9px;border:1px solid #d1d5db;border-radius:7px;"></label>
                <label>متن جایگزین<input name="alt_text" value="{{ old('alt_text', $item->alt_text) }}" maxlength="500" style="width:100%;margin-top:5px;padding:9px;border:1px solid #d1d5db;border-radius:7px;"></label>
                <label>توضیح<textarea name="caption" rows="4" style="width:100%;margin-top:5px;padding:9px;border:1px solid #d1d5db;border-radius:7px;">{{ old('caption', $item->caption) }}</textarea></label>
                <button class="btn btn-primary" type="submit">ذخیره اطلاعات</button>
            </form>
            <hr style="border:0;border-top:1px solid #e5e7eb;margin:18px 0;">
            <h2 style="font-size:15px;margin:0 0 10px;">وضعیت استفاده</h2>
            @forelse($usages as $usage)<p style="font-size:13px;margin:8px 0;padding:8px;background:#fffbeb;border-radius:6px;">{{ $usage->consumer_type }} · {{ $usage->reference_path }}</p>@empty<p style="font-size:13px;color:#047857;">این رسانه بدون استفاده است و می‌تواند حذف شود.</p>@endforelse
            <form method="POST" action="{{ route('admin.media.destroy', $item->uuid) }}" onsubmit="return confirm('رسانه حذف شود؟');">@csrf @method('DELETE')<button class="btn btn-danger" type="submit" @disabled($usages->isNotEmpty())>حذف رسانه</button></form>
        </section>
    </div>
</div>
<style>@media(max-width:700px){.media-detail-grid{grid-template-columns:1fr!important}}</style>
@endsection
