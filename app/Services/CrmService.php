<?php

namespace App\Services;

use App\Models\CrmContact;
use App\Models\CrmDeal;
use App\Models\CrmActivity;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CrmService
{
    public function dashboardStats(): array
    {
        $totalContacts  = CrmContact::count();
        $newThisMonth   = CrmContact::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)->count();

        $activeDeals    = CrmDeal::whereNotIn('stage', ['won', 'lost'])->count();
        $pipelineValue  = CrmDeal::whereNotIn('stage', ['won', 'lost'])->sum('value');
        $wonThisMonth   = CrmDeal::where('stage', 'won')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)->count();
        $wonValue       = CrmDeal::where('stage', 'won')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)->sum('value');

        $recentActivities = CrmActivity::with('contact', 'deal')
            ->latest()->take(10)->get();

        $stageBreakdown = CrmDeal::selectRaw('stage, COUNT(*) as count, SUM(value) as total_value')
            ->whereNotIn('stage', ['won', 'lost'])
            ->groupBy('stage')
            ->get()
            ->keyBy('stage');

        $statusBreakdown = CrmContact::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return compact(
            'totalContacts', 'newThisMonth', 'activeDeals',
            'pipelineValue', 'wonThisMonth', 'wonValue',
            'recentActivities', 'stageBreakdown', 'statusBreakdown'
        );
    }

    public function importFromOrders(): int
    {
        $imported = 0;
        $orders = Order::whereNotNull('customer_mobile')
            ->select('customer_name', 'customer_mobile', 'customer_email')
            ->distinct()
            ->get();

        foreach ($orders as $order) {
            $exists = CrmContact::where('mobile', $order->customer_mobile)->exists();
            if (!$exists && $order->customer_mobile) {
                CrmContact::create([
                    'name'   => $order->customer_name ?? 'مشتری',
                    'mobile' => $order->customer_mobile,
                    'email'  => $order->customer_email,
                    'source' => 'order',
                    'status' => 'customer',
                ]);
                $imported++;
            }
        }
        return $imported;
    }

    public function createContact(array $data): CrmContact
    {
        return CrmContact::create($data);
    }

    public function updateContact(CrmContact $contact, array $data): CrmContact
    {
        $contact->update($data);
        return $contact->fresh();
    }

    public function createDeal(array $data): CrmDeal
    {
        return CrmDeal::create($data);
    }

    public function updateDeal(CrmDeal $deal, array $data): CrmDeal
    {
        $deal->update($data);
        return $deal->fresh();
    }

    public function moveDeal(CrmDeal $deal, string $newStage): CrmDeal
    {
        $data = ['stage' => $newStage];
        if (in_array($newStage, ['won', 'lost'])) {
            $data['closed_at'] = now();
        }
        $deal->update($data);
        return $deal;
    }

    public function logActivity(array $data): CrmActivity
    {
        return CrmActivity::create($data);
    }

    public function pipelineByStage(): array
    {
        $stages = array_keys(CrmDeal::stages());
        $deals  = CrmDeal::with('contact')->orderByDesc('created_at')->get();

        $pipeline = [];
        foreach ($stages as $stage) {
            $pipeline[$stage] = $deals->where('stage', $stage)->values();
        }
        return $pipeline;
    }
}
