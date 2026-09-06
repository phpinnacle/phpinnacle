<?php

namespace PHPinnacle\Franz\Response;

use PHPinnacle\Franz\Support\ResponseValue;

final readonly class WorkerInfoResponse
{
    /**
     * @param array<array-key, mixed> $raw
     */
    public function __construct(
        public string $version,
        public string $commit,
        public string $kafkaClusterId,
        public array $raw,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::string($payload['version'] ?? null),
            ResponseValue::string($payload['commit'] ?? null),
            ResponseValue::string($payload['kafka_cluster_id'] ?? null),
            $payload,
        );
    }
}
