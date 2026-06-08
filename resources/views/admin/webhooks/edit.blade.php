@extends('admin.layout')
@section('title', 'ویرایش Webhook')
@section('header', 'ویرایش Webhook Endpoint')
@section('content')
<div class="admin-page max-w-2xl">
    <form method="post" action="{{ route('admin.webhooks.update', $webhook) }}">
        @csrf @method('PUT')
        <div class="card">
            <div class="card-header">اطلاعات Endpoint</div>
            <div class="card-body space-y-5">

                <div>
                    <label class="form-label">آدرس URL <span class="text-red-500">*</span></label>
                    <input type="url" name="url" value="{{ old('url', $webhook->url) }}"
                           class="form-input @error('url') border-red-500 @enderror"
                           dir="ltr" required>
                    @error('url')<p class="form-hint text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="form-label">توضیح (اختیاری)</label>
                    <input type="text" name="description" value="{{ old('description', $webhook->description) }}"
                           class="form-input">
                </div>

                <div>
                    <label class="form-label">رویدادها <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-1 gap-2 mt-2">
                        @foreach($availableEvents as $key => $label)
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox" name="events[]" value="{{ $key }}"
                                       class="rounded border-slate-300 text-indigo-600"
                                       {{ in_array($key, old('events', $webhook->events ?? [])) ? 'checked' : '' }}>
                                <span class="flex-1">
                                    <span class="text-sm font-medium text-slate-800">{{ $label }}</span>
                                    <span class="block font-mono text-xs text-slate-400 mt-0.5">{{ $key }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('events')<p class="form-hint text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           class="rounded border-slate-300 text-indigo-600"
                           {{ old('is_active', $webhook->is_active ? '1' : '') ? 'checked' : '' }}>
                    <label for="is_active" class="text-sm text-slate-700 cursor-pointer">فعال</label>
                </div>

                {{-- Secret section --}}
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                    <div class="text-sm font-medium text-slate-700 mb-2">Secret Key</div>
                    <code class="font-mono text-xs text-slate-600 break-all">{{ $webhook->secret }}</code>
                    <form method="post" action="{{ route('admin.webhooks.rotate-secret', $webhook) }}" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm"
                                onclick="return confirm('تولید secret جدید همه integrations فعلی را می‌شکند. ادامه می‌دهید؟');">
                            تولید Secret جدید
                        </button>
                    </form>
                </div>

            </div>
        </div>

        <div class="admin-page-tools mt-4">
            <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
            <a href="{{ route('admin.webhooks.show', $webhook) }}" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
</div>
@endsection
