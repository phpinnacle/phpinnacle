<?php

namespace PHPinnacle\Langcat\Response\Groups;

use PHPinnacle\Langcat\Support\ResponseValue;
use UnexpectedValueException;

final readonly class GroupSearchResponse
{
    /** @param list<GroupTeacherSearchResponse> $teachers */
    public function __construct(
        public int $id,
        public ?GroupBasicResponse $basic,
        public array $teachers,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $basic = ResponseValue::nullableObject($payload, 'group.basic');
        $teachers = $payload['teachers'] ?? [];

        if (!is_array($teachers) || !array_is_list($teachers)) {
            throw new UnexpectedValueException('Response field [teachers] must be a list.');
        }

        return new self(
            ResponseValue::int($payload, 'id'),
            $basic === null ? null : GroupBasicResponse::fromArray($basic),
            array_map(
                static fn (mixed $teacher) => GroupTeacherSearchResponse::fromArray(
                    is_array($teacher)
                        ? $teacher
                        : throw new UnexpectedValueException('Each teacher must be an object.'),
                ),
                $teachers,
            ),
        );
    }
}
