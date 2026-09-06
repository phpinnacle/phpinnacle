<?php

namespace PHPinnacle\Franz\Response;

use PHPinnacle\Franz\Support\ResponseValue;

final readonly class TaskResponse
{
    /**
     * @param array<string, string> $config
     * @param array<array-key, mixed> $raw
     */
    public function __construct(
        public TaskIdResponse $id,
        public array $config,
        public array $raw,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            TaskIdResponse::fromArray(ResponseValue::object($payload['id'] ?? null)),
            ResponseValue::config($payload['config'] ?? null),
            $payload,
        );
    }
}
