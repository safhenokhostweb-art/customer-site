<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerMobileAuthController extends Controller
{
    public function token(Request $request): JsonResponse
    {
        $request->validate([
            'mobile'   => 'required|string|regex:/^09[0-9]{9}$/',
            'password' => 'required|string',
        ]);

        $user = User::where('mobile', $request->mobile)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'شماره موبایل یا رمز عبور اشتباه است.'], 401);
        }

        // تولید توکن ساده
        $plainToken = Str::random(60);
        $user->update(['api_token' => hash('sha256', $plainToken)]);

        return response()->json([
            'data' => [
                'token'      => $plainToken,
                'expires_at' => now()->addDays(30)->toIso8601String(),
                'user' => [
                    'id'            => $user->id,
                    'name'          => $user->name ?? $user->mobile,
                    'mobile'        => $user->mobile,
                    'is_admin'      => (bool) ($user->is_admin      ?? false),
                    'is_site_admin' => (bool) ($user->is_site_admin ?? false),
                    'site_id'       => null,
                ],
                'session' => [
                    'session_id' => 'cs-' . $user->id . '-' . now()->timestamp,
                    'expires_at' => now()->addDays(30)->toIso8601String(),
                ],
            ],
            'meta' => ['tenant_id' => 'customer-site'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = auth()->user();
        if ($user) {
            $user->update(['api_token' => null]);
        }
        return response()->json(['message' => 'خروج موفق.']);
    }
}
