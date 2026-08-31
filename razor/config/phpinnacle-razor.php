<?php

return [
    'navigation' => [
        'template' => [
            'sort' => 99,
            'icon' => 'phosphor-paint-brush-broad',
        ],
        'document' => [
            'icon' => 'phosphor-file-doc',
            'sort' => 3,
        ],
    ],
    'auth' => [
        'model' => 'App\\Models\\User',
    ],
    'tenancy' => null,
    //    'tenancy' => [
    //        'model' => App\\Models\\Tenant::class,
    //        'default' => App\\Models\\Tenant::DEFAULT,
    //    ],
];
