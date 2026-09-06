<?php

namespace PHPinnacle\Franz\Response;

use PHPinnacle\Franz\Support\ResponseValue;

final readonly class ConfigValidationResponse
{
    /**
     * @param list<string> $groups
     * @param list<ConfigValidationResultResponse> $configs
     * @param array<array-key, mixed> $raw
     */
    public function __construct(
        public string $name,
        public int $errorCount,
        public array $groups,
        public array $configs,
        public array $raw,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::string($payload['name'] ?? null),
            ResponseValue::int($payload['error_count'] ?? null),
            ResponseValue::strings($payload['groups'] ?? null),
            array_map(
                ConfigValidationResultResponse::fromArray(...),
                ResponseValue::objects($payload['configs'] ?? null),
            ),
            $payload,
        );
    }

    public function isValid(): bool
    {
        return $this->errorCount === 0;
    }
}
