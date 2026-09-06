<?php

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use PHPinnacle\Langcat\Client;
use PHPinnacle\Langcat\Request\Authorization\AuthenticateRequest;

$packageAutoload = dirname(__DIR__) . '/vendor/autoload.php';
$monorepoAutoload = dirname(__DIR__, 3) . '/vendor/autoload.php';

require file_exists($packageAutoload) ? $packageAutoload : $monorepoAutoload;

$baseUri = getenv('LANGLION_BASE_URI');
$clientId = getenv('LANGLION_CLIENT_ID');
$clientSecret = getenv('LANGLION_CLIENT_SECRET');

if (
    $baseUri === false
    || $baseUri === ''
    || $clientId === false
    || $clientId === ''
    || $clientSecret === false
    || $clientSecret === ''
) {
    fwrite(
        STDERR,
        "Set LANGLION_BASE_URI, LANGLION_CLIENT_ID, and LANGLION_CLIENT_SECRET before running an example.\n",
    );
    exit(1);
}

$factory = new HttpFactory;
$client = new Client($baseUri, new HttpClient(['timeout' => 30]), $factory, $factory);

// Authenticate once per run and keep the token in memory.
$token = $client
    ->authorization()
    ->authenticate(
        AuthenticateRequest::make()->clientId($clientId)->clientSecret($clientSecret),
    );

return $client->withAccessToken($token->accessToken);
