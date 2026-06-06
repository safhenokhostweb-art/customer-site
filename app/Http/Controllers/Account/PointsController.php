<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\MembershipPointsService;

class PointsController extends Controller
{
    public function __construct(private MembershipPointsService $service) {}

    public function index()
    {
        $user    = auth()->user();
        $balance = $this->service->getBalance($user);
        $history = $this->service->getHistory($user, 20);

        return view('account.points.index', compact('balance', 'history'));
    }
}
