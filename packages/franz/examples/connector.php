<?php

use PHPinnacle\Franz\Client;

$action = $argv[1] ?? '';
$name = $argv[2] ?? '';
$actions = [
    'status',
    'pause',
    'resume',
    'stop',
    'restart',
    'restart-failed',
    'restart-task',
    'task-status',
    'reset-topics',
    'delete',
];

if (!in_array($action, $actions, true) || $name === '') {
    fwrite(STDERR, 'Usage: php connector.php <' . implode('|', $actions) . "> <name> [task-id]\n");
    exit(2);
}

$taskId = null;

if ($action === 'restart-task' || $action === 'task-status') {
    $taskId = filter_var($argv[3] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

    if ($taskId === false) {
        fwrite(STDERR, "Provide a non-negative task ID, for example 0.\n");
        exit(2);
    }
}

/** @var Client $connect */
$connect = require __DIR__ . '/bootstrap.php';
$connector = $connect->connector($name);

if ($action === 'status') {
    echo json_encode($connector->status()->raw, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
    exit(0);
}

if ($taskId !== null) {
    $task = $connector->task($taskId);

    if ($action === 'task-status') {
        echo json_encode($task->status()->raw, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
        exit(0);
    }

    $task->restart();
    echo "Restart requested for {$name}, task {$taskId}.\n";
    exit(0);
}

match ($action) {
    'pause' => $connector->pause(),
    'resume' => $connector->resume(),
    'stop' => $connector->stop(),
    'restart' => $connector->restart(includeTasks: true),
    'restart-failed' => $connector->restart(includeTasks: true, onlyFailed: true),
    'reset-topics' => $connector->resetTopics(),
    'delete' => $connector->delete(),
};

echo "Requested {$action} for {$name}. Use the status command to observe asynchronous transitions.\n";
