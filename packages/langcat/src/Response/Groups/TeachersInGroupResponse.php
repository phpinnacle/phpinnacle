<?php

namespace PHPinnacle\Langcat\Response\Groups;

use PHPinnacle\Langcat\Response\Shared\PaginationResponse;
use PHPinnacle\Langcat\Support\ResponseValue;
use UnexpectedValueException;

final readonly class TeachersInGroupResponse
{
    /** @param list<TeacherInGroupResponse> $data */
    public function __construct(
        public array $data,
        public PaginationResponse $meta,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            array_map(
                static fn (mixed $item) => TeacherInGroupResponse::fromArray(
                    is_array($item)
                        ? $item
                        : throw new UnexpectedValueException('Each group teacher must be an object.'),
                ),
                ResponseValue::list($payload, 'data'),
            ),
            PaginationResponse::fromArray(ResponseValue::object($payload, 'meta')),
        );
    }
}
