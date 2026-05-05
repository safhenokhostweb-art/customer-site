<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\BusinessCard;

class BusinessCardController extends Controller
{
    public function show(string $slug)
    {
        $card = BusinessCard::where('slug', $slug)->where('is_active', true)->firstOrFail();
        return view('business-card.show', compact('card'));
    }
}
