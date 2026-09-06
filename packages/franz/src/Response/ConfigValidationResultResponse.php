<?php

namespace PHPinnacle\Franz\Response;

use PHPinnacle\Franz\Support\ResponseValue;

final readonly class ConfigValidationResultResponse
{
    /**
     * @param array<array-key, mixed> $raw
     */
    public function __construct(
        public ?ConfigDefinitionResponse $definition,
        public ConfigValueResponse $value,
        public array $raw,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ($payload['definition'] ?? null) !== null
                ? ConfigDefinitionResponse::fromArray(ResponseValue::object($payload['definition']))
                : null,
            ConfigValueResponse::fromArray(ResponseValue::object($payload['value'] ?? null)),
            $payload,
        );
    }
}
