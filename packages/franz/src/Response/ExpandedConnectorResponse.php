<?php

namespace PHPinnacle\Franz\Response;

use PHPinnacle\Franz\Support\ResponseValue;

final readonly class ExpandedConnectorResponse
{
    /**
     * @param array<array-key, mixed> $raw
     */
    public function __construct(
        public ConnectorResponse $info,
        public ConnectorStatusResponse $status,
        public array $raw,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ConnectorResponse::fromArray(ResponseValue::object($payload['info'] ?? null)),
            ConnectorStatusResponse::fromArray(ResponseValue::object($payload['status'] ?? null)),
            $payload,
        );
    }
}
