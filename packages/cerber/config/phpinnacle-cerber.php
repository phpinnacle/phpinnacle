<?php

return [
    'auth_providers' => [
        // Laravel\Socialite\Two\GoogleProvider::class,
        // Laravel\Socialite\Two\GithubProvider::class,
    ],
    'navigation' => [
        'user' => [
            'sort' => 80,
            'icon' => 'phosphor-users',
        ],
        'role' => [
            'sort' => 81,
            'icon' => 'phosphor-shield-check',
        ],
        'provider' => [
            'sort' => 82,
            'icon' => 'phosphor-key',
        ],
    ],
    'translations' => [],
    'permissions' => [],
    'exclude' => [
        'pages' => [],
        'widgets' => [],
        'resources' => [],
    ],
    'api' => [
        'callback_url' => env('CERBER_API_CALLBACK_URL', ''),
    ],

    /*
     |--------------------------------------------------------------------------
     | Database Settings
     |--------------------------------------------------------------------------
     |
     | Connection name for Cerber models. Set to null to use default connection.
     |
     */
    'connection' => null,
];
