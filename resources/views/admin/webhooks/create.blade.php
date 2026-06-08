@extends('admin.layout')
@section('title', 'Webhook جدید')
@section('header', 'تعریف Webhook Endpoint')
@section('content')
<div class="admin-page max-w-2xl">
    <form method="post" action="{{ route('admin.webhooks.store') }}">
        @csrf
        <div class="card">
            <div class="card-header">اطلاعات Endpoint</div>
            <div class="card-body space-y-5">

                <div>
                    <label class="form-label">آدرس URL <span class="text-red-500">*</span></label>
                    <input type="url" name="url" value="{{ old('url') }}"
                           class="form-input @error('url') border-red-500 @enderror"
                           placeholder="https://your-service.com/webhook"
                           dir="ltr" required>
                    @error('url')<p class="form-hint text-red-500">{{ $message }}</p>@enderror
                    <p class="form-hint">آدرس باید HTTPS باشد و POST request را بپذیرد.</p>
                </div>

                <div>
                    <label class="form-label">توضیح (اختیاری)</label>
                    <input type="text" name="description" value="{{ old('description') }}"
                           class="form-input" placeholder="مثال: CRM شرکت">
                </div>

                <div>
                    <label class="form-label">رویدادها <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-1 gap-2 mt-2">
                        @foreach($availableEvents as $key => $label)
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox" name="events[]" value="{{ $key }}"
                                       class="rounded border-slate-300 text-indigo-600"
                                       {{ in_array($key, old('events', [])) ? 'checked' : '' }}>
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
                    <input type="checkbox" name="is_active" id="is_active" value="1" class="rounded border-slate-300 text-indigo-600"
                           {{ old('is_active', '1') ? 'checked' : '' }}>
                    <label for="is_active" class="text-sm text-slate-700 cursor-pointer">فعال</label>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
                    <strong>نکته امنیتی:</strong> یک secret key به صورت خودکار تولید می‌شود.
                    هر درخواست webhook دارای header
                    <code class="font-mono bg-amber-100 px-1 rounded">X-Webhook-Signature: sha256=...</code>
                    است که می‌توانید برای تأیید صحت استفاده کنید.
                </div>

            </div>
        </div>

        <div class="admin-page-tools mt-4">
            <button type="submit" class="btn btn-primary">ایجاد Webhook</button>
            <a href="{{ route('admin.webhooks.index') }}" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
</div>
@endsection
