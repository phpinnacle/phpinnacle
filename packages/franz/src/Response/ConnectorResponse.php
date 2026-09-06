<?php

namespace PHPinnacle\Franz\Response;

use PHPinnacle\Franz\Support\ResponseValue;

final readonly class ConnectorResponse
{
    /**
     * @param array<string, string> $config
     * @param list<TaskIdResponse> $tasks
     * @param array<array-key, mixed> $raw
     */
    public function __construct(
        public string $name,
        public array $config,
        public array $tasks,
        public ?string $type,
        public array $raw,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::string($payload['name'] ?? null),
            ResponseValue::config($payload['config'] ?? null),
            array_map(TaskIdResponse::fromArray(...), ResponseValue::objects($payload['tasks'] ?? null)),
            ($payload['type'] ?? null) !== null ? ResponseValue::string($payload['type']) : null,
            $payload,
        );
    }
}
