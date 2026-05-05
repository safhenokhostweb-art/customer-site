<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserAddressRequest;
use App\Models\UserAddress;
use App\Models\UserActivityLog;

class ProfileAddressController extends Controller
{
    public function index()
    {
        $addresses = auth()->user()->addresses()->latest()->get();

        return view('account.profile.addresses', compact('addresses'));
    }

    public function store(UserAddressRequest $request)
    {
        $user = auth()->user();
        $data = $request->validated();
        $data['user_id'] = $user->id;
        $data['is_default'] = (bool) ($data['is_default'] ?? false);

        if ($data['is_default'] || $user->addresses()->count() === 0) {
            $data['is_default'] = true;
        }

        UserAddress::create($data);

        UserActivityLog::log('address_created', 'افزودن آدرس جدید');

        return back()->with('success', 'آدرس با موفقیت اضافه شد.');
    }

    public function update(UserAddressRequest $request, UserAddress $address)
    {
        if ($address->user_id !== auth()->id()) {
            abort(404);
        }

        $data = $request->validated();
        $data['is_default'] = (bool) ($data['is_default'] ?? false);
        $address->update($data);

        UserActivityLog::log('address_updated', 'به‌روزرسانی آدرس');

        return back()->with('success', 'آدرس با موفقیت به‌روزرسانی شد.');
    }

    public function setDefault(UserAddress $address)
    {
        if ($address->user_id !== auth()->id()) {
            abort(404);
        }

        $address->update(['is_default' => true]);
        UserActivityLog::log('address_default_set', 'تنظیم آدرس پیش‌فرض');

        return back()->with('success', 'آدرس به‌عنوان پیش‌فرض تنظیم شد.');
    }

    public function destroy(UserAddress $address)
    {
        if ($address->user_id !== auth()->id()) {
            abort(404);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            auth()->user()->addresses()->first()?->update(['is_default' => true]);
        }

        UserActivityLog::log('address_deleted', 'حذف آدرس');

        return back()->with('success', 'آدرس حذف شد.');
    }
}
