<?php

use PHPinnacle\Canvio\Client;

if ($argc !== 2) {
    fwrite(STDERR, "Usage: php examples/list-modules.php <course-id>\n");
    exit(1);
}

/** @var Client $canvas */
$canvas = require __DIR__ . '/bootstrap.php';

// Generic endpoints need URL-encoded path segments, including SIS identifiers.
$path = '/courses/' . rawurlencode($argv[1]) . '/modules';

foreach ($canvas->paginate($path, ['per_page' => 100]) as $module) {
    echo json_encode($module, JSON_THROW_ON_ERROR) . "\n";
}
