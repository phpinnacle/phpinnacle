<?php

use PHPinnacle\Franz\Client;
use PHPinnacle\Franz\Request\ConnectorConfigRequest;
use PHPinnacle\Franz\Request\PatchConnectorConfigRequest;

$action = $argv[1] ?? '';
$name = $argv[2] ?? '';
$file = $argv[3] ?? '';

if (!in_array($action, ['replace', 'patch'], true) || $name === '' || !is_readable($file)) {
    fwrite(STDERR, "Usage: php configure-connector.php <replace|patch> <name> <config.json>\n");
    exit(2);
}

$payload = json_decode(file_get_contents($file), flags: JSON_THROW_ON_ERROR);

if (!$payload instanceof stdClass) {
    throw new InvalidArgumentException('Configuration must be a JSON object.');
}

$config = get_object_vars($payload);

foreach ($config as $value) {
    if (!is_string($value) && !($action === 'patch' && $value === null)) {
        throw new InvalidArgumentException(
            'Configuration values must be strings; PATCH also accepts null to remove a key.',
        );
    }
}

/** @var Client $connect */
$connect = require __DIR__ . '/bootstrap.php';
$connector = $connect->connector($name);

// Use an authoritative full configuration for replace, not a potentially masked API response.
$result = $action === 'replace'
    ? $connector->updateConfig(new ConnectorConfigRequest($config))
    : $connector->patchConfig(new PatchConnectorConfigRequest($config));

echo "Updated {$result->name}. Configuration changes may restart its tasks.\n";
