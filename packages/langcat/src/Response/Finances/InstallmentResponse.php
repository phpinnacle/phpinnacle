<?php

namespace PHPinnacle\Langcat\Response\Finances;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class InstallmentResponse
{
    /** @param list<int> $tagIds */
    public function __construct(
        public int $id,
        public int|float|null $value,
        public int|float|null $paidValue,
        public string $dueDate,
        public array $tagIds,
        public string $createdAt,
        public ?string $dateTo,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::nullableNumber($payload, 'value'),
            ResponseValue::nullableNumber($payload, 'paidValue'),
            ResponseValue::string($payload, 'dueDate'),
            ResponseValue::integers($payload, 'tagIds'),
            ResponseValue::string($payload, 'createdAt'),
            ResponseValue::nullableString($payload, 'dateTo'),
        );
    }
}
