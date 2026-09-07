<?php

return [
    'currency' => env('BARBERSHOP_CURRENCY', 'IRR'),
    'base_price' => (int) env('BARBERSHOP_BASE_PRICE', 0),
    'deposit_percentage' => (int) env('BARBERSHOP_DEPOSIT_PERCENTAGE', 30),
    'slot_duration_minutes' => (int) env('BARBERSHOP_SLOT_DURATION_MINUTES', 30),
    'hold_minutes' => (int) env('BARBERSHOP_HOLD_MINUTES', 10),
    'admin_seed' => [
        'name' => env('ADMIN_SEED_NAME'),
        'email' => env('ADMIN_SEED_EMAIL'),
        'password' => env('ADMIN_SEED_PASSWORD'),
    ],
];
