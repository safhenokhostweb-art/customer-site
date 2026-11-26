<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Services\AssetProvisioning\AssetEntitlementClient;
use App\Services\PlatformApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use SaaSPlatform\ThemeRuntime\Access\AssetRef;

class CanvasMarketplaceController extends Controller
{
    public function __construct(
        protected PlatformApiService $platform,
        protected AssetEntitlementClient $entitlements,
    ) {}

    public function index(Request $request): View
    {
        // Handle return from payment gateway
        if ($request->query('payment') === 'success') {
            $ref = $request->query('ref');
            $successMsg = 'پرداخت با موفقیت انجام شد و دارایی به سایت شما افزوده گردید.' . ($ref ? " (کد پیگیری: {$ref})" : '');
            session()->now('success', $successMsg);
            $this->entitlements->forget();
            $this->entitlements->refresh();
        } elseif ($request->query('payment') === 'failed') {
            $failMsg = $request->query('msg', 'پرداخت لغو شد یا با خطا مواجه گردید.');
            session()->now('error', $failMsg);
        }

        $platformUrl = config('platform.api_url') ?: config('site.platform_url');
        if (empty($platformUrl)) {
            return view('admin.canvas-marketplace.index', [
                'error' => 'اتصال به پلتفرم ابری SaaS پیکربندی نشده است.',
                'catalog' => ['buttons' => [], 'icons' => [], 'headers' => [], 'motions' => []],
                'activeTab' => 'buttons',
            ]);
        }

        $activeTab = $request->query('tab', 'buttons');
        $typeFilter = $request->query('type', 'all'); // all, free, paid
        $statusFilter = $request->query('status', 'all'); // all, owned, unowned

        $catalog = $this->platform->canvasMarketplaceAssets();
        $siteEntitlements = $this->entitlements->current();

        // Harmonize entitlement status across catalog
        foreach (['buttons', 'icons', 'headers', 'motions'] as $group) {
            if (!isset($catalog[$group]) || !is_array($catalog[$group])) {
                $catalog[$group] = [];
                continue;
            }

            foreach ($catalog[$group] as &$item) {
                $ref = ($item['kind'] ?? '') . ':' . ($item['slug'] ?? '');
                // An asset is granted if platform reported it or site already holds local grant
                $isGranted = !empty($item['is_granted']) || $siteEntitlements->hasGrant($ref);
                $item['is_granted'] = $isGranted;
            }
            unset($item);

            // Apply filters if requested
            if ($typeFilter !== 'all') {
                $catalog[$group] = array_values(array_filter($catalog[$group], fn ($item) => ($item['access_type'] ?? 'free') === $typeFilter));
            }
            if ($statusFilter === 'owned') {
                $catalog[$group] = array_values(array_filter($catalog[$group], fn ($item) => !empty($item['is_granted'])));
            } elseif ($statusFilter === 'unowned') {
                $catalog[$group] = array_values(array_filter($catalog[$group], fn ($item) => empty($item['is_granted'])));
            }
        }

        return view('admin.canvas-marketplace.index', [
            'catalog' => $catalog,
            'activeTab' => $activeTab,
            'typeFilter' => $typeFilter,
            'statusFilter' => $statusFilter,
            'error' => null,
        ]);
    }

    public function claim(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'asset_kind' => 'required|string',
            'asset_key' => 'required|string',
        ]);

        $kind = $data['asset_kind'];
        $key = $data['asset_key'];

        $result = $this->platform->canvasMarketplaceClaim($kind, $key);

        if (!empty($result['success'])) {
            // Force immediate synchronization of tenant entitlements
            $this->entitlements->forget();
            $this->entitlements->refresh();

            $tab = match ($kind) {
                AssetRef::KIND_BUTTON_PACK => 'buttons',
                AssetRef::KIND_ICON_PACK => 'icons',
                AssetRef::KIND_HEADER => 'headers',
                AssetRef::KIND_MOTION => 'motions',
                default => 'buttons',
            };

            return redirect()->route('admin.canvas-marketplace.index', ['tab' => $tab])
                ->with('success', $result['message'] ?? 'دارایی با موفقیت دریافت و فعال شد.');
        }

        return redirect()->back()
            ->with('error', $result['message'] ?? 'خطا در دریافت دارایی رایگان.');
    }

    public function purchase(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'asset_kind' => 'required|string',
            'asset_key' => 'required|string',
        ]);

        $kind = $data['asset_kind'];
        $key = $data['asset_key'];

        $tab = match ($kind) {
            AssetRef::KIND_BUTTON_PACK => 'buttons',
            AssetRef::KIND_ICON_PACK => 'icons',
            AssetRef::KIND_HEADER => 'headers',
            AssetRef::KIND_MOTION => 'motions',
            default => 'buttons',
        };

        $redirectBack = route('admin.canvas-marketplace.index', ['tab' => $tab]);

        $result = $this->platform->canvasMarketplacePurchase($kind, $key, [
            'redirect_back_url' => $redirectBack,
        ]);

        // If gateway returned a redirection URL, forward user directly to the bank payment page
        if (!empty($result['redirect_url'])) {
            return redirect()->away($result['redirect_url']);
        }

        if (!empty($result['success'])) {
            // Invalidate local entitlement cache so new grant is immediately available in Canvas
            $this->entitlements->forget();
            $this->entitlements->refresh();

            return redirect()->route('admin.canvas-marketplace.index', ['tab' => $tab])
                ->with('success', $result['message'] ?? 'خرید با موفقیت انجام شد و دسترسی فعال گردید.');
        }

        return redirect()->back()
            ->with('error', $result['message'] ?? ($result['error'] ?? 'خطا در انجام خرید دارایی.'));
    }
}
