<?php

namespace PHPinnacle\Franz\Value;

use PHPinnacle\Franz\Support\ResponseValue;
use UnexpectedValueException;

final readonly class ConnectorOffset
{
    /**
     * @param array<array-key, mixed> $partition
     * @param array<array-key, mixed>|null $offset Null resets this partition's offset.
     */
    public function __construct(
        public array $partition,
        public ?array $offset,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        if (!array_key_exists('offset', $payload)) {
            throw new UnexpectedValueException('Expected a response offset field, which may explicitly be null.');
        }

        return new self(
            ResponseValue::object($payload['partition'] ?? null),
            $payload['offset'] === null ? null : ResponseValue::object($payload['offset']),
        );
    }

    /** @return array{partition: object, offset: object|null} */
    public function toArray(): array
    {
        return [
            'partition' => (object) $this->partition,
            'offset' => $this->offset === null ? null : (object) $this->offset,
        ];
    }
}
