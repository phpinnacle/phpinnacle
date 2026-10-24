<?php

namespace PHPinnacle\Franz\Context;

use PHPinnacle\Franz\Enum\LoggerScope;
use PHPinnacle\Franz\Enum\LogLevel;
use PHPinnacle\Franz\Response\LoggerResponse;
use PHPinnacle\Franz\Support\ResourcePath;
use PHPinnacle\Franz\Support\ResponseValue;
use PHPinnacle\Franz\Support\Transport;

final readonly class LoggerContext
{
    private string $path;

    public function __construct(
        private Transport $transport,
        string $name,
    ) {
        $this->path = '/admin/loggers/' . ResourcePath::segment($name);
    }

    public function details(): LoggerResponse
    {
        return LoggerResponse::fromArray(ResponseValue::object($this->transport->send('GET', $this->path)));
    }

    /** @return list<string>|null Affected logger names, or null for a cluster-wide acknowledgement. */
    public function setLevel(LogLevel $level, LoggerScope $scope = LoggerScope::Worker): ?array
    {
        $payload = $this->transport->send('PUT', $this->path, ['scope' => $scope->value], ['level' => $level->value]);

        return ResponseValue::strings($payload, nullable: true);
    }
}
