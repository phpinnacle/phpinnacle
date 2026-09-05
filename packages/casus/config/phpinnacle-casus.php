<?php

return [
    'navigation' => [
        'exception' => [
            'icon' => 'phosphor-bug',
            'sort' => 1000,
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
