<?php

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use PHPinnacle\Canvio\Client;

$packageAutoload = dirname(__DIR__) . '/vendor/autoload.php';
$monorepoAutoload = dirname(__DIR__, 3) . '/vendor/autoload.php';

require file_exists($packageAutoload) ? $packageAutoload : $monorepoAutoload;

$baseUri = getenv('CANVAS_BASE_URI');
$accessToken = getenv('CANVAS_ACCESS_TOKEN');

if ($baseUri === false || $baseUri === '' || $accessToken === false || $accessToken === '') {
    fwrite(STDERR, "Set CANVAS_BASE_URI and CANVAS_ACCESS_TOKEN before running an example.\n");
    exit(1);
}

$factory = new HttpFactory;

return new Client(
    $baseUri,
    new HttpClient(['timeout' => 30]),
    $factory,
    $factory,
    $accessToken,
);
