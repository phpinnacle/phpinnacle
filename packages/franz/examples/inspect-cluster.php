<?php

use PHPinnacle\Franz\Client;

/** @var Client $connect */
$connect = require __DIR__ . '/bootstrap.php';

$worker = $connect->info();
echo "Kafka Connect {$worker->version}, Kafka cluster {$worker->kafkaClusterId}\n";

foreach ($connect->plugins() as $plugin) {
    echo "Plugin: {$plugin->class} ({$plugin->type}, {$plugin->version})\n";
}

foreach ($connect->expandedConnectors() as $name => $entry) {
    echo "\n{$name}: {$entry->status->connector->state} on {$entry->status->connector->workerId}\n";

    foreach ($entry->status->tasks as $task) {
        echo "  Task {$task->id}: {$task->state} on {$task->workerId}\n";

        if ($task->trace !== null) {
            echo "  {$task->trace}\n";
        }
    }

    echo '  Topics: ' . implode(', ', $connect->connector($name)->topics()) . "\n";
}
