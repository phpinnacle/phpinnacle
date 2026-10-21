<?php

return [
    'navigation' => [
        'exception' => [
            'icon' => 'phosphor-bug',
            'sort' => 1000,
        ],
    ],
    'frontend' => [
        'enabled' => false,
        'rate_limits' => [
            'global' => [
                1 => 20,
                10 => 100,
                60 => 300,
            ],
            'per_ip' => [
                1 => 3,
                10 => 10,
                60 => 30,
            ],
        ],
    ],
    'prune' => 30,
    'connection' => null,
    'redaction' => [
        'replacement' => '[hidden]',
        'keys' => [
            'authorization',
            'cookie',
            'csrf_token',
            'credential',
            'password',
            'remember',
            'secret',
            'session',
            'token',
        ],
    ],
    'notifications' => [
        'mail' => null,
        'repeat_threshold' => null,
    ],
    'tenancy' => null,
    //    'tenancy' => [
    //        'model' => App\\Models\\Tenant::class,
    //        'default' => App\\Models\\Tenant::DEFAULT,
    //    ],
];
