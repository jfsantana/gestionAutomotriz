@props(['name'])

@php
    $icon = match ($name) {
        'dashboard' => "\u{1F4CA}",
        'customers' => "\u{1F465}",
        'vehicles' => "\u{1F697}",
        'services' => "\u{1F527}",
        'maintenance-orders' => "\u{1F4DD}",
        'reminders' => "\u{1F514}",
        'company-profile' => "\u{1F3A8}",
        'staff' => "\u{1F468}\u{200D}\u{1F527}",
        'companies' => "\u{1F3E2}",
        'company-admins', 'account' => "\u{1F464}",
        'subscription-plans' => "\u{1F4BC}",
        'customer-subscription-rates' => "\u{1F4B2}",
        'company-subscriptions' => "\u{1F4B3}",
        'client-subscriptions' => "\u{1F4B3}",
        'policies' => "\u{1F4DC}",
        'personal-maintenance' => "\u{2795}",
    };
@endphp

<span {{ $attributes }} aria-hidden="true">{{ $icon }}</span>