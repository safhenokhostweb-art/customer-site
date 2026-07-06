@extends('admin.layout')
@section('header', 'پروفایل من')

@section('content')
<div x-data="{
    isSaving: false,
    showSuccess: false,
    avatarPreview: null,
    previewAvatar(e) {
        const f = e.target.files[0];
        if (!f) return;
        const r = new FileReader();
        r.onload = ev => { this.avatarPreview = ev.target.result; };
        r.readAsDataURL(f);
    },
    async save(form) {
        this.isSaving = true;
        try {
            const fd = new FormData(form);
            fd.append('_method', 'PUT');
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                body: fd
            });
            const data = await res.json();
            if (data.success) {
                if (data.avatar_url) {
                    document.querySelectorAll('.admin-avatar-img').forEach(el => el.src = data.avatar_url);
                    document.querySelectorAll('.admin-avatar-initials').forEach(el => el.style.display = 'none');
                    document.querySelectorAll('.admin-avatar-img').forEach(el => el.style.display = '');
                }
                this.showSuccess = true;
                setTimeout(() => this.showSuccess = false, 3500);
            }
        } finally { this.isSaving = false; }
    }
}" class="max-w-3xl mx-auto space-y-5">

    {{-- Force password change notice --}}
    @if(session('force_password_change_notice') || auth()->user()->force_password_change)
    <div class="flex items-start gap-3 bg-amber-50 border border-amber-300 text-amber-800 px-4 py-3 rounded-2xl text-sm font-medium">
        <x-core-ui::icon name="exclamation-triangle" class="w-5 h-5 shrink-0 mt-0.5" />
        <span>برای ادامه استفاده از پنل، ابتدا باید رمز عبور خود را تغییر دهید. پس از تغییر رمز، دسترسی کامل به پنل برقرار می‌شود.</span>
    </div>
    @endif

    {{-- Success --}}
    <div x-show="showSuccess" x-transition
         class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-2xl text-sm font-medium"
         style="display:none">
        <x-core-ui::icon name="check-circle" class="w-5 h-5 shrink-0" />
        پروفایل با موفقیت به‌روزرسانی شد.
    </div>


    <form action="{{ route('admin.profile.update') }}" method="POST"
          enctype="multipart/form-data" @submit.prevent="save($el)">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

            {{-- ─── Avatar Card ────────────────────────────── --}}
            <div class="md:col-span-1">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col items-center gap-4">
                    {{-- Avatar preview --}}
                    <div class="relative group">
                        @php $avatarUrl = $user->avatar ? url($user->avatar) : null; @endphp
                        <template x-if="avatarPreview">
                            <img :src="avatarPreview" class="w-24 h-24 rounded-full object-cover ring-4 ring-white shadow-lg admin-avatar-img">
                        </template>
                        <template x-if="!avatarPreview">
                            @if($avatarUrl)
                            <img src="{{ $avatarUrl }}" class="w-24 h-24 rounded-full object-cover ring-4 ring-white shadow-lg admin-avatar-img">
                            @else
                            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center ring-4 ring-white shadow-lg admin-avatar-initials">
                                <span class="text-3xl font-bold text-white">{{ mb_substr($user->name ?? $user->mobile, 0, 1) }}</span>
                            </div>
                            @endif
                        </template>
                        <label class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-full opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                            <x-core-ui::icon name="image" class="w-6 h-6 text-white" />
                            <input type="file" name="avatar" accept="image/*" class="hidden" @change="previewAvatar($event)">
                        </label>
                    </div>
                    <div class="text-center">
                        <p class="font-semibold text-gray-800 text-sm">{{ $user->name ?? 'ادمین' }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $user->mobile }}</p>
                        <span class="inline-flex items-center gap-1 mt-1.5 px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 text-[10px] font-semibold">
                            <x-core-ui::icon name="crown" class="w-2.5 h-2.5" />
                            مدیر سیستم
                        </span>
                    </div>
                    <p class="text-[10px] text-gray-400 text-center">روی تصویر کلیک کنید تا آن را تغییر دهید<br>JPG، PNG — حداکثر ۲ مگابایت</p>
                </div>
            </div>

            {{-- ─── Info + Password ─────────────────────────── --}}
            <div class="md:col-span-2 flex flex-col gap-4">

                {{-- Basic info --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                        <x-core-ui::icon name="user" class="w-4 h-4 text-indigo-500" />
                        اطلاعات پایه
                    </h2>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1.5">نام و نام خانوادگی</label>
                            <input type="text" name="name" value="{{ $user->name }}"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all"
                                   placeholder="نام خود را وارد کنید">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1.5">شماره موبایل</label>
                            <input type="text" value="{{ $user->mobile }}" disabled dir="ltr"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-gray-100 text-sm bg-gray-50 text-gray-400 cursor-not-allowed">
                            <p class="text-[10px] text-gray-400 mt-1">شماره موبایل قابل ویرایش نیست.</p>
                        </div>
                    </div>
                </div>

                {{-- Password --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                        <x-core-ui::icon name="lock" class="w-4 h-4 text-indigo-500" />
                        تغییر رمز عبور
                    </h2>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1.5">رمز عبور جدید</label>
                            <input type="password" name="password" dir="ltr"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all"
                                   placeholder="خالی = بدون تغییر">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1.5">تکرار رمز عبور جدید</label>
                            <input type="password" name="password_confirmation" dir="ltr"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all"
                                   placeholder="تکرار رمز عبور جدید">
                        </div>
                    </div>
                </div>

                {{-- Save button --}}
                <div class="flex justify-end">
                    <button type="submit" :disabled="isSaving"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-semibold shadow-md hover:shadow-lg transition-all disabled:opacity-60">
                        <x-core-ui::icon x-show="isSaving" name="loader" class="w-4 h-4 animate-spin" />
                        <x-core-ui::icon x-show="!isSaving" name="save" class="w-4 h-4" />
                        <span x-text="isSaving ? 'در حال ذخیره...' : 'ذخیره تغییرات'"></span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
