<?php

namespace PHPinnacle\Langcat\Response\Students;

use PHPinnacle\Langcat\Response\Shared\DetailsResponse;
use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class AdvancedParentResponse
{
    public function __construct(
        public int $id,
        public ?ParentResponse $basic,
        public ?DetailsResponse $details,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $id = ResponseValue::int($payload, 'id');
        $basic = ResponseValue::nullableObject($payload, 'parents.basic') ?? ResponseValue::nullableObject(
            $payload,
            'parents.basics',
        );
        $details = ResponseValue::nullableObject($payload, 'parents.details');

        return new self(
            $id,
            $basic === null ? null : ParentResponse::fromArray(['id' => $id, ...$basic]),
            $details === null ? null : DetailsResponse::fromArray($details),
        );
    }
}
