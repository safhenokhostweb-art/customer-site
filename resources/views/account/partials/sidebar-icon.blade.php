@php
    $iconMap = [
        'overview'      => 'dashboard',
        'profile'       => 'user',
        'orders'        => 'order',
        'subscriptions' => 'subscription',
        'installments'  => 'credit-card',
        'forms'         => 'assignment',
        'notifications' => 'bell',
        'security'      => 'lock',
        'tickets'       => 'message',
        'points'        => 'spark',
        'referrals'     => 'referral',
        'addresses'     => 'map-pin',
        'wallet'        => 'wallet',
        'courses'       => 'courses',
        'activity'      => 'activity',
        'deletion'      => 'trash',
        'members'       => 'users',
    ];
    $iconName = $iconMap[$key ?? ''] ?? 'dots-horizontal';
@endphp
<x-core-ui::icon :name="$iconName" class="w-5 h-5 ml-3 shrink-0" />
