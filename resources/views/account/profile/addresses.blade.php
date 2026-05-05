@extends('account.layout')
@section('title', 'آدرس‌ها')
@section('header', 'مدیریت آدرس‌ها')
@section('content')
<div class="space-y-6 max-w-3xl">
    <div class="account-card">
        <div class="account-card-header">افزودن آدرس جدید</div>
        <div class="account-card-body">
            <form method="POST" action="{{ route('account.profile.addresses.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">برچسب *</label>
                    <input type="text" name="label" value="{{ old('label', 'آدرس پیش‌فرض') }}" required class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">آدرس *</label>
                    <input type="text" name="address_line1" value="{{ old('address_line1') }}" required class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">آدرس تکمیلی</label>
                    <input type="text" name="address_line2" value="{{ old('address_line2') }}" class="form-input">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">شهر</label>
                        <input type="text" name="city" value="{{ old('city') }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">استان</label>
                        <input type="text" name="state" value="{{ old('state') }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">کد پستی</label>
                        <input type="text" name="postal_code" value="{{ old('postal_code') }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">کشور</label>
                        <input type="text" name="country" value="{{ old('country', 'ایران') }}" class="form-input">
                    </div>
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_default" value="0">
                        <input type="checkbox" name="is_default" value="1">
                        <span>آدرس پیش‌فرض</span>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">افزودن آدرس</button>
            </form>
        </div>
    </div>
    <div class="account-card">
        <div class="account-card-header">آدرس‌های من</div>
        <div class="account-card-body">
            @if($addresses->isEmpty())
                <p class="text-slate-500 py-4">هنوز آدرسی ثبت نشده است.</p>
            @else
                @foreach($addresses as $addr)
                    <div class="p-4 rounded-lg border border-slate-200 mb-3 {{ $addr->is_default ? 'border-indigo-300 bg-indigo-50/50' : '' }}">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-medium">{{ $addr->label }} @if($addr->is_default)<span class="badge badge-info text-xs">پیش‌فرض</span>@endif</div>
                                <p class="text-slate-600 mt-1">{{ $addr->full_address }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if(!$addr->is_default)
                                    <form method="POST" action="{{ route('account.profile.addresses.default', $addr) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-secondary btn-sm">پیش‌فرض</button>
                                    </form>
                                @endif
                                <a href="#" onclick="document.getElementById('edit-{{ $addr->id }}').classList.toggle('hidden'); return false;" class="btn btn-secondary btn-sm">ویرایش</a>
                                <form method="POST" action="{{ route('account.profile.addresses.destroy', $addr) }}" class="inline" onsubmit="return confirm('آیا از حذف اطمینان دارید؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                </form>
                            </div>
                        </div>
                        <div id="edit-{{ $addr->id }}" class="hidden mt-4 pt-4 border-t border-slate-200">
                            <form method="POST" action="{{ route('account.profile.addresses.update', $addr) }}">
                                @csrf
                                @method('PUT')
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label class="form-label">برچسب</label>
                                        <input type="text" name="label" value="{{ $addr->label }}" required class="form-input">
                                    </div>
                                    <div class="form-group md:col-span-2">
                                        <label class="form-label">آدرس</label>
                                        <input type="text" name="address_line1" value="{{ $addr->address_line1 }}" required class="form-input">
                                    </div>
                                    <div class="form-group md:col-span-2">
                                        <label class="form-label">آدرس تکمیلی</label>
                                        <input type="text" name="address_line2" value="{{ $addr->address_line2 }}" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">شهر</label>
                                        <input type="text" name="city" value="{{ $addr->city }}" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">استان</label>
                                        <input type="text" name="state" value="{{ $addr->state }}" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">کد پستی</label>
                                        <input type="text" name="postal_code" value="{{ $addr->postal_code }}" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">کشور</label>
                                        <input type="text" name="country" value="{{ $addr->country }}" class="form-input">
                                    </div>
                                    <div class="form-group md:col-span-2">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="hidden" name="is_default" value="0">
                                            <input type="checkbox" name="is_default" value="1" {{ $addr->is_default ? 'checked' : '' }}>
                                            <span>آدرس پیش‌فرض</span>
                                        </label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">ذخیره</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
    <a href="{{ route('account.profile.index') }}" class="btn btn-secondary">بازگشت به پروفایل</a>
</div>
@endsection
