@extends('admin.layout')
@section('title', 'ویدیوهای صفحه نخست')

@section('content')
<div class="admin-page-container">
    <div class="admin-page-header flex items-center justify-between mb-6">
        <div>
            <h1 class="admin-page-title">ویدیوهای صفحه نخست</h1>
            <p class="admin-page-subtitle text-sm text-slate-500 mt-1">مدیریت ویدیوهای نمایش‌داده‌شده در صفحه اصلی</p>
        </div>
        <a href="{{ route('admin.homepage-videos.create') }}" class="btn btn-primary gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            افزودن ویدیو
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    @if($videos->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-slate-400">
            <svg class="w-16 h-16 mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.723v6.554a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <p class="text-lg font-medium">هنوز ویدیویی اضافه نشده</p>
            <a href="{{ route('admin.homepage-videos.create') }}" class="mt-4 btn btn-primary btn-sm">افزودن اولین ویدیو</a>
        </div>
    @else
        <div class="grid gap-4" id="videos-list">
            @foreach($videos as $video)
            <div class="bg-white border border-slate-200 rounded-2xl p-4 flex items-center gap-4 shadow-sm" data-id="{{ $video->id }}">
                {{-- Drag handle --}}
                <div class="text-slate-300 cursor-grab hover:text-slate-500 drag-handle shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                </div>

                {{-- Thumbnail --}}
                <div class="w-24 h-16 rounded-xl overflow-hidden bg-slate-100 shrink-0 flex items-center justify-center">
                    @if($video->thumbnail_url)
                        <img src="{{ \App\Helpers\ImageHelper::url($video->thumbnail_url) }}" alt="" class="w-full h-full object-cover">
                    @elseif($video->video_type === 'youtube' && $video->youtubeId())
                        <img src="https://img.youtube.com/vi/{{ $video->youtubeId() }}/mqdefault.jpg" alt="" class="w-full h-full object-cover">
                    @else
                        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endif
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-slate-800 truncate">{{ $video->title ?: 'بدون عنوان' }}</p>
                    <p class="text-xs text-slate-400 mt-0.5 truncate" dir="ltr">{{ Str::limit($video->video_url, 60) }}</p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $video->video_type === 'youtube' ? 'bg-red-100 text-red-700' : ($video->video_type === 'cloudflare' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700') }}">
                            {{ match($video->video_type) { 'youtube' => 'YouTube', 'cloudflare' => 'Cloudflare', default => 'مستقیم' } }}
                        </span>
                        @if($video->autoplay)
                            <span class="text-xs px-2 py-0.5 rounded-full bg-violet-100 text-violet-700">اتوپلی</span>
                        @endif
                    </div>
                </div>

                {{-- Status toggle --}}
                <div class="shrink-0">
                    <span class="inline-flex items-center gap-1.5 text-sm {{ $video->is_active ? 'text-green-600' : 'text-slate-400' }}">
                        <span class="w-2 h-2 rounded-full {{ $video->is_active ? 'bg-green-500' : 'bg-slate-300' }}"></span>
                        {{ $video->is_active ? 'فعال' : 'غیرفعال' }}
                    </span>
                </div>

                {{-- Actions --}}
                <div class="shrink-0 flex items-center gap-2">
                    <a href="{{ route('admin.homepage-videos.edit', $video) }}"
                       class="btn btn-secondary btn-sm">ویرایش</a>
                    <form method="POST" action="{{ route('admin.homepage-videos.destroy', $video) }}"
                          onsubmit="return confirm('ویدیو حذف شود؟')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>

        <p class="text-xs text-slate-400 mt-4 text-center">برای تغییر ترتیب نمایش، ردیف‌ها را بکشید.</p>
    @endif
</div>

@push('scripts')
<script>
(function() {
    // Simple drag-to-reorder (no external library needed)
    var list = document.getElementById('videos-list');
    if (!list) return;
    var dragging = null;

    list.querySelectorAll('[data-id]').forEach(function(row) {
        row.draggable = true;
        row.addEventListener('dragstart', function() { dragging = row; row.style.opacity = '.4'; });
        row.addEventListener('dragend', function() { row.style.opacity = '1'; saveOrder(); });
        row.addEventListener('dragover', function(e) {
            e.preventDefault();
            var rect = row.getBoundingClientRect();
            var mid  = rect.top + rect.height / 2;
            if (e.clientY < mid) list.insertBefore(dragging, row);
            else list.insertBefore(dragging, row.nextSibling);
        });
    });

    function saveOrder() {
        var ids = Array.from(list.querySelectorAll('[data-id]')).map(function(r, i) { return r.dataset.id; });
        fetch('{{ route("admin.homepage-videos.reorder") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            body: JSON.stringify({ order: ids })
        });
    }
})();
</script>
@endpush
@endsection
