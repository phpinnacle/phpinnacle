<?php

return [
    'upload' => [
        'disk' => env('PINAX_MEDIA_DISK', 'public'),
        'folder' => env('PINAX_MEDIA_FOLDER', 'media'),
    ],
    'connection' => null,
    'tenancy' => null,
    //    'tenancy' => [
    //        'model' => 'App\\Models\\Tenant',
    //        'default' => 'App\\Models\\Tenant::DEFAULT'
    //    ],
];
