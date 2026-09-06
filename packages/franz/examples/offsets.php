<?php

use PHPinnacle\Franz\Client;
use PHPinnacle\Franz\Request\AlterConnectorOffsetsRequest;
use PHPinnacle\Franz\Value\ConnectorOffset;

$action = $argv[1] ?? '';
$name = $argv[2] ?? '';

if (!in_array($action, ['show', 'set-sink', 'reset-sink-partition', 'reset-all'], true) || $name === '') {
    fwrite(
        STDERR,
        "Usage: php offsets.php <show|set-sink|reset-sink-partition|reset-all> <name> [topic partition [offset]]\n",
    );
    exit(2);
}

$partition = null;
$offset = null;
$topic = $argv[3] ?? '';

if ($action === 'set-sink' || $action === 'reset-sink-partition') {
    $partition = filter_var($argv[4] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

    if ($partition === false || $topic === '') {
        throw new InvalidArgumentException('Provide a topic and a non-negative partition number.');
    }
}

if ($action === 'set-sink') {
    $offset = filter_var($argv[5] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

    if ($offset === false) {
        throw new InvalidArgumentException('Provide a non-negative Kafka offset.');
    }
}

/** @var Client $connect */
$connect = require __DIR__ . '/bootstrap.php';
$connector = $connect->connector($name);

if ($action === 'show') {
    echo json_encode(array_map(
        static fn (ConnectorOffset $item) => $item->toArray(),
        $connector->offsets(),
    ), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
    exit(0);
}

// Offset changes require STOPPED. Failure leaves the connector stopped for inspection.
$connector->stop();
$deadline = microtime(true) + 30;

while ($connector->status()->connector->state !== 'STOPPED') {
    if (microtime(true) >= $deadline) {
        throw new RuntimeException('Connector did not stop within 30 seconds; offsets were not changed.');
    }

    usleep(250_000);
}

$result = $action === 'reset-all'
    ? $connector->resetOffsets()
    : $connector->alterOffsets(new AlterConnectorOffsetsRequest([
        new ConnectorOffset(
            partition: ['kafka_topic' => $topic, 'kafka_partition' => $partition],
            offset: $offset === null ? null : ['kafka_offset' => $offset],
        ),
    ]));

echo $result->message . "\n";
$connector->resume();
echo "Resume requested for {$name}.\n";
