<?php

namespace PHPinnacle\Langcat\Response\Groups;

use PHPinnacle\Langcat\Enum\AssignType;
use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class StudentInGroupResponse
{
    public function __construct(
        public int $id,
        public AssignType $assignType,
        public string $enrolledAt,
        public ?string $dischargedAt,
        public ?StudentAgreementResponse $agreement,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $agreement = ResponseValue::nullableObject($payload, 'agreement');

        return new self(
            ResponseValue::int($payload, 'id'),
            AssignType::from(ResponseValue::string($payload, 'assignType')),
            ResponseValue::string($payload, 'enrolledAt'),
            ResponseValue::nullableString($payload, 'dischargedAt'),
            $agreement === null ? null : StudentAgreementResponse::fromArray($agreement),
        );
    }
}
