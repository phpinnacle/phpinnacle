<?php

namespace PHPinnacle\Langcat\Response\Students;

use PHPinnacle\Langcat\Response\Shared\PaginationResponse;
use PHPinnacle\Langcat\Support\ResponseValue;
use UnexpectedValueException;

final readonly class StudentsResponse
{
    /** @param list<StudentResponse> $data */
    public function __construct(
        public array $data,
        public PaginationResponse $meta,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $students = array_map(
            static fn (mixed $student) => StudentResponse::fromArray(
                is_array($student) ? $student : throw new UnexpectedValueException('Each student must be an object.'),
            ),
            ResponseValue::list($payload, 'data'),
        );

        $meta = $payload['meta'] ?? null;

        return new self(
            $students,
            PaginationResponse::fromArray(
                is_array($meta)
                    ? $meta
                    : throw new UnexpectedValueException('Response field [meta] must be an object.'),
            ),
        );
    }
}
