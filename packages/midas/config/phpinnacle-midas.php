<?php

return [
    'providers' => [
        PHPinnacle\Midas\Payments\Cash::class,
        PHPinnacle\Midas\Payments\Bank::class,
        PHPinnacle\Midas\Payments\Card::class,
        PHPinnacle\Midas\Payments\BePaid::class,
        PHPinnacle\Midas\Payments\Erip::class,
        PHPinnacle\Midas\Payments\Stripe::class,
        PHPinnacle\Midas\Payments\WebPay::class,
    ],
    'queue' => [
        'connection' => 'database',
        'queue' => 'payments',
    ],
    'navigation' => [
        'transaction' => [
            'sort' => 39,
            'icon' => 'phosphor-receipt',
        ],
        'payment_plan' => [
            'sort' => 40,
            'icon' => 'phosphor-wallet',
        ],
        'payment_method' => [
            'sort' => 41,
            'icon' => 'phosphor-credit-card',
        ],
    ],
    'connection' => null,
    'tenancy' => null,
    //    'tenancy' => [
    //        'model' => 'App\\Models\\Tenant',
    //        'default' => 'App\\Models\\Tenant::DEFAULT'
    //    ],
];
