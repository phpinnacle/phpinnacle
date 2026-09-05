<?php

namespace PHPinnacle\Langcat\Response\Documents;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class DocumentTemplateMarginResponse
{
    public function __construct(
        public int $top,
        public int $left,
        public int $right,
        public int $bottom,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'top'),
            ResponseValue::int($payload, 'left'),
            ResponseValue::int($payload, 'right'),
            ResponseValue::int($payload, 'bottom'),
        );
    }
}
