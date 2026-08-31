<?php

return [
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

    /*
     |--------------------------------------------------------------------------
     | Allowed Domains for Auto-Registration
     |--------------------------------------------------------------------------
     |
     | List of email domains that are allowed to auto-register via OAuth.
     | Example: ['domain.com']
     |
     */
    'allowed_domains' => [],

    /*
     |--------------------------------------------------------------------------
     | Default Role for New OAuth Users
     |--------------------------------------------------------------------------
     |
     | Role name to assign to new users created via OAuth.
     | Set to null to skip role assignment.
     |
     */
    'default_role' => null,

    /*
     |--------------------------------------------------------------------------
     | Cache Settings
     |--------------------------------------------------------------------------
     |
     | Cache configuration for OAuth provider settings from database.
     |
     */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'key' => 'oauth_providers',
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
