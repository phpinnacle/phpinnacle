<?php

namespace PHPinnacle\Langcat\Response\GroupSettings;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class AgeGroupResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int|float|string $ageFrom,
        public int|float|string $ageTo,
        public int $schoolId,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::string($payload, 'name'),
            ResponseValue::numberOrString($payload, 'ageFrom'),
            ResponseValue::numberOrString($payload, 'ageTo'),
            ResponseValue::int($payload, 'schoolId'),
        );
    }
}
