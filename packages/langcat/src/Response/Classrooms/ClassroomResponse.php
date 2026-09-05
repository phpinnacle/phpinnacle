<?php

namespace PHPinnacle\Langcat\Response\Classrooms;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class ClassroomResponse
{
    /** @param list<int> $schoolIds */
    public function __construct(
        public int $id,
        public string $name,
        public array $schoolIds,
        public int $maxStudentsNumber,
        public string $address,
        public string $description,
        public string $color,
        public ?bool $isExternal,
        public bool $isArchived,
        public ?string $archivedAt,
        public string $createdAt,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::string($payload, 'name'),
            ResponseValue::integers($payload, 'schoolIds'),
            ResponseValue::int($payload, 'maxStudentsNumber'),
            ResponseValue::string($payload, 'address'),
            ResponseValue::string($payload, 'description'),
            ResponseValue::string($payload, 'color'),
            ResponseValue::nullableBool($payload, 'isExternal'),
            ResponseValue::bool($payload, 'isArchived'),
            ResponseValue::nullableString($payload, 'archivedAt'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}
