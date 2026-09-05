<?php

namespace PHPinnacle\Langcat\Response\Shared;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class PaginationResponse
{
    public function __construct(
        public int $count,
        public int $total,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'count'),
            ResponseValue::int($payload, 'total'),
        );
    }
}
