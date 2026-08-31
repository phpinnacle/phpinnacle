<?php

return [
    'prune' => [
        'enabled' => true,
        'days' => 7,
    ],
    'user' => [
        'model' => 'App\\Models\\User',
    ],
    'connection' => null,
    'tenancy' => null,
    //    'tenancy' => [
    //        'model' => 'App\\Models\\Tenant',
    //        'default' => 'App\\Models\\Tenant::DEFAULT'
    //    ],
];
