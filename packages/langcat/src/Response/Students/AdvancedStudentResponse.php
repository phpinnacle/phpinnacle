<?php

namespace PHPinnacle\Langcat\Response\Students;

use PHPinnacle\Langcat\Response\Shared\AccessResponse;
use PHPinnacle\Langcat\Response\Shared\DetailsResponse;
use PHPinnacle\Langcat\Support\ResponseValue;
use UnexpectedValueException;

final readonly class AdvancedStudentResponse
{
    /** @param list<AdvancedParentResponse> $parents */
    public function __construct(
        public int $id,
        public ?StudentBasicResponse $basic,
        public ?DetailsResponse $details,
        public ?AccessResponse $access,
        public array $parents,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $basic = ResponseValue::nullableObject($payload, 'student.basic');
        $details = ResponseValue::nullableObject($payload, 'student.details');
        $access = ResponseValue::nullableObject($payload, 'student.access.login');
        $parents = $payload['parents'] ?? [];

        if (!is_array($parents)) {
            throw new UnexpectedValueException('Response field [parents] must be an object or list.');
        }

        if ($parents !== [] && !array_is_list($parents)) {
            $parents = [$parents];
        }

        return new self(
            ResponseValue::int($payload, 'id'),
            $basic === null ? null : StudentBasicResponse::fromArray($basic),
            $details === null ? null : DetailsResponse::fromArray($details),
            $access === null ? null : AccessResponse::fromArray($access),
            array_map(
                static fn (mixed $parent) => AdvancedParentResponse::fromArray(
                    is_array($parent) ? $parent : throw new UnexpectedValueException('Each parent must be an object.'),
                ),
                $parents,
            ),
        );
    }
}
