<?php

namespace PHPinnacle\Franz\Response;

use PHPinnacle\Franz\Support\ResponseValue;

final readonly class ConnectorStatusResponse
{
    /**
     * @param list<TaskStatusResponse> $tasks
     * @param array<array-key, mixed> $raw
     */
    public function __construct(
        public string $name,
        public ConnectorStateResponse $connector,
        public array $tasks,
        public ?string $type,
        public array $raw,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::string($payload['name'] ?? null),
            ConnectorStateResponse::fromArray(ResponseValue::object($payload['connector'] ?? null)),
            array_map(TaskStatusResponse::fromArray(...), ResponseValue::objects($payload['tasks'] ?? null)),
            ResponseValue::string($payload['type'] ?? null, nullable: true),
            $payload,
        );
    }
}
