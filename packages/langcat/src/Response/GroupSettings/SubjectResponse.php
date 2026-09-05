<?php

namespace PHPinnacle\Langcat\Response\GroupSettings;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class SubjectResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $shortName,
        public string $color,
        public int $schoolId,
        public bool $isShared,
        public string $description,
        public int $languageId,
        public int $levelId,
        public string $createdAt,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::string($payload, 'name'),
            ResponseValue::string($payload, 'shortName'),
            ResponseValue::string($payload, 'color'),
            ResponseValue::int($payload, 'schoolId'),
            ResponseValue::bool($payload, 'isShared'),
            ResponseValue::string($payload, 'description'),
            ResponseValue::int($payload, 'languageId'),
            ResponseValue::int($payload, 'levelId'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}
