<?php

namespace PHPinnacle\Franz\Context;

use InvalidArgumentException;
use PHPinnacle\Franz\Response\TaskStatusResponse;
use PHPinnacle\Franz\Support\ResourcePath;
use PHPinnacle\Franz\Support\ResponseValue;
use PHPinnacle\Franz\Support\Transport;

final readonly class TaskContext
{
    private string $path;

    public function __construct(
        private Transport $transport,
        string $connectorName,
        int $taskId,
    ) {
        if ($taskId < 0) {
            throw new InvalidArgumentException('Task ID cannot be negative.');
        }

        $this->path = '/connectors/' . ResourcePath::segment($connectorName) . '/tasks/' . $taskId;
    }

    public function status(): TaskStatusResponse
    {
        return TaskStatusResponse::fromArray(ResponseValue::object($this->transport->send(
            'GET',
            $this->path . '/status',
        )));
    }

    public function restart(): void
    {
        $this->transport->send('POST', $this->path . '/restart');
    }
}
