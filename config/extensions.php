<?php

/*
|--------------------------------------------------------------------------
| Extension Product Availability (Phase 3E-E, corrected)
|--------------------------------------------------------------------------
|
| Two DELIBERATELY SEPARATE concepts — never conflate them:
|
| 'installed'    Extension packages whose code is genuinely present on
|                this installation, keyed by product identity
|                "{vendor-slug}/{package-slug}" (ExtensionProductIdentity).
|                This is NOT licensing/entitlement and NOT Marketplace
|                fulfillment — it only records "the code is here."
|
| 'entitlements' Which product identities each tenant/site is permitted to
|                use, keyed by that tenant's own License.license_key (a
|                stable, persistent identity — never a request-derived
|                value such as the current request's Host header). A
|                package being 'installed' above does NOT by itself grant
|                any tenant access to it — see
|                ConfigExtensionEntitlementProvider.
|
| This is a V1 bridge only, not a Marketplace/licensing system: no
| purchasing, orders, fulfillment, or billing exist here or are implied.
| A future real entitlement source can replace 'entitlements' behind
| ExtensionEntitlementProviderContract without changing anything upstream.
|
| Both are empty by default: no third-party extension exists yet, and the
| default behavior for any tenant/product not explicitly listed is
| unavailable (fail closed) — never silently granted.
|
*/

return [
    'installed' => [
        // 'vendor-slug/package-slug' => ['version' => '1.0.0'],
    ],

    'entitlements' => [
        // 'the-tenant-license-key' => ['vendor-slug/package-slug'],
    ],
];
