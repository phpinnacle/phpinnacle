<?php

namespace PHPinnacle\Langcat\Response\GroupSettings;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class ProgramItemResponse
{
    /** @param list<ProgramItemElementResponse> $elements */
    public function __construct(
        public int $id,
        public int $position,
        public array $elements,
        public string $createdAt,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::int($payload, 'position'),
            ResponseValue::objects($payload, 'elements', ProgramItemElementResponse::fromArray(...)),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}
