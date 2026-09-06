<?php

use PHPinnacle\Franz\Client;
use PHPinnacle\Franz\Request\ConnectorConfigRequest;
use PHPinnacle\Franz\Request\CreateConnectorRequest;

/** @var Client $connect */
$connect = require __DIR__ . '/bootstrap.php';

$prefix = getenv('FRANZ_EXAMPLE_PREFIX');
$prefix = $prefix === false ? 'franz-example' : $prefix;
$topic = getenv('FRANZ_EXAMPLE_TOPIC');
$topic = $topic === false ? 'franz-example-lines' : $topic;
$inputFile = getenv('FRANZ_EXAMPLE_INPUT');
$outputFile = getenv('FRANZ_EXAMPLE_OUTPUT');
$converters = [
    'key.converter' => 'org.apache.kafka.connect.storage.StringConverter',
    'value.converter' => 'org.apache.kafka.connect.storage.StringConverter',
];

// Both paths refer to the Connect worker's filesystem, not the PHP machine.
$requests = [
    new CreateConnectorRequest($prefix . '-sink', [
        'name' => $prefix . '-sink',
        'connector.class' => 'org.apache.kafka.connect.file.FileStreamSinkConnector',
        'tasks.max' => '1',
        'file' => $outputFile === false ? '/data/output.txt' : $outputFile,
        'topics' => $topic,
        ...$converters,
    ]),
    new CreateConnectorRequest($prefix . '-source', [
        'name' => $prefix . '-source',
        'connector.class' => 'org.apache.kafka.connect.file.FileStreamSourceConnector',
        'tasks.max' => '1',
        'file' => $inputFile === false ? '/data/input.txt' : $inputFile,
        'topic' => $topic,
        ...$converters,
    ]),
];

// Validate both configurations before creating either connector.
foreach ($requests as $request) {
    $validation = $connect
        ->plugin($request->config['connector.class'])
        ->validateConfig(new ConnectorConfigRequest($request->config));

    if (!$validation->isValid()) {
        foreach ($validation->configs as $field) {
            foreach ($field->value->errors as $error) {
                fwrite(STDERR, "{$request->name}: {$field->value->name}: {$error}\n");
            }
        }

        exit(1);
    }
}

foreach ($requests as $request) {
    $connector = $connect->createConnector($request);
    echo "Created {$connector->name}\n";
}

echo "Append lines to the input file on the Connect worker and inspect the output file.\n";
echo "Run inspect-cluster.php to check task states; creation does not wait for tasks to start.\n";
