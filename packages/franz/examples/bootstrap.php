<?php

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use PHPinnacle\Franz\Client;

$packageAutoload = dirname(__DIR__) . '/vendor/autoload.php';
$monorepoAutoload = dirname(__DIR__, 3) . '/vendor/autoload.php';

require file_exists($packageAutoload) ? $packageAutoload : $monorepoAutoload;

$headers = [];
$authorization = getenv('KAFKA_CONNECT_AUTHORIZATION');

if ($authorization !== false && $authorization !== '') {
    $headers['Authorization'] = $authorization;
}

$factory = new HttpFactory;
$baseUri = getenv('KAFKA_CONNECT_URL');

return new Client(
    $baseUri === false ? 'http://localhost:8083' : $baseUri,
    new HttpClient(['connect_timeout' => 5, 'timeout' => 30]),
    $factory,
    $factory,
    $headers,
);
