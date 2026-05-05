<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ProfileService;

class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $profileService
    ) {}

    public function index()
    {
        $user = auth()->user();
        $user->load('addresses');

        return view('account.profile.index', compact('user'));
    }

    public function update(ProfileUpdateRequest $request)
    {
        $this->profileService->updateProfile($request->user(), $request->validated());

        return back()->with('success', 'پروفایل با موفقیت به‌روزرسانی شد.');
    }
}
