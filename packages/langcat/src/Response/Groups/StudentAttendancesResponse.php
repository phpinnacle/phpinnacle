<?php

namespace PHPinnacle\Langcat\Response\Groups;

use UnexpectedValueException;

final readonly class StudentAttendancesResponse
{
    /** @param list<StudentAttendanceResponse> $data */
    public function __construct(
        public array $data,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        if (!array_is_list($payload)) {
            throw new UnexpectedValueException('Attendance response must be a list.');
        }

        return new self(array_map(
            static fn (mixed $item) => StudentAttendanceResponse::fromArray(
                is_array($item) ? $item : throw new UnexpectedValueException('Each attendance must be an object.'),
            ),
            $payload,
        ));
    }
}
