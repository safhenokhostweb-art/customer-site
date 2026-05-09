<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Http\Request;

class ShopWishlistService
{
    public function __construct(protected Request $request) {}

    public function wishlist(): Wishlist
    {
        if (auth()->check()) {
            return Wishlist::query()->firstOrCreate(['user_id' => auth()->id()], ['session_id' => null]);
        }
        $sid = $this->sessionId();

        return Wishlist::query()->firstOrCreate(
            ['session_id' => $sid],
            ['user_id' => null]
        );
    }

    public function sessionId(): string
    {
        if (! $this->request->session()->has('shop_guest_id')) {
            $this->request->session()->put('shop_guest_id', bin2hex(random_bytes(16)));
        }

        return (string) $this->request->session()->get('shop_guest_id');
    }

    public function toggle(Product $product): bool
    {
        $w = $this->wishlist();
        $item = WishlistItem::query()->where('wishlist_id', $w->id)->where('product_id', $product->id)->first();
        if ($item) {
            $item->delete();

            return false;
        }
        WishlistItem::query()->create(['wishlist_id' => $w->id, 'product_id' => $product->id]);

        return true;
    }

    public function has(int $productId): bool
    {
        $w = $this->wishlist();

        return WishlistItem::query()->where('wishlist_id', $w->id)->where('product_id', $productId)->exists();
    }
}
