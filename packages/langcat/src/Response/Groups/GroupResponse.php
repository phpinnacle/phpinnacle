<?php

namespace PHPinnacle\Langcat\Response\Groups;

use PHPinnacle\Langcat\Enum\GradingType;
use PHPinnacle\Langcat\Enum\GroupType;
use PHPinnacle\Langcat\Support\ResponseValue;
use UnexpectedValueException;

final readonly class GroupResponse
{
    // @mago-expect lint:excessive-parameter-list
    public function __construct(
        public int $id,
        public string $name,
        public int $schoolId,
        public string $startsOn,
        public ?string $endsOn,
        public int $numberOfLessons,
        public int $lessonDuration,
        public GroupType|int $type,
        public GroupBillingResponse $billing,
        public ?int $minStudents,
        public ?int $maxStudents,
        public GradingType $gradingType,
        public ?int $languageId,
        public ?int $levelId,
        public ?int $programCollectionId,
        public ?int $documentTemplateId,
        public ?int $installmentCollectionId,
        public ?int $companyId,
        public int|float|null $companyPrice,
        public bool $isPlanned,
        public string $createdAt,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $type = $payload['type'] ?? null;

        if (!is_int($type) && !is_string($type)) {
            throw new UnexpectedValueException('Response field [type] must be an integer or string.');
        }

        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::string($payload, 'name'),
            ResponseValue::int($payload, 'schoolId'),
            ResponseValue::string($payload, 'startsOn'),
            ResponseValue::nullableString($payload, 'endsOn'),
            ResponseValue::int($payload, 'numberOfLessons'),
            ResponseValue::int($payload, 'lessonDuration'),
            is_string($type) ? GroupType::from($type) : $type,
            GroupBillingResponse::fromArray(ResponseValue::object($payload, 'billing')),
            ResponseValue::nullableInt($payload, 'minStudents'),
            ResponseValue::nullableInt($payload, 'maxStudents'),
            GradingType::from(ResponseValue::string($payload, 'gradingType')),
            ResponseValue::nullableInt($payload, 'languageId'),
            ResponseValue::nullableInt($payload, 'levelId'),
            ResponseValue::nullableInt($payload, 'programCollectionId'),
            ResponseValue::nullableInt($payload, 'documentTemplateId'),
            ResponseValue::nullableInt($payload, 'installmentCollectionId'),
            ResponseValue::nullableInt($payload, 'companyId'),
            ResponseValue::nullableNumber($payload, 'companyPrice'),
            ResponseValue::bool($payload, 'isPlanned'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}
