<?php

namespace PHPinnacle\Langcat\Response\Groups;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class LessonResponse
{
    /** @param list<int> $teachers */
    public function __construct(
        public int $id,
        public string $startAt,
        public string $endAt,
        public int $length,
        public ?int $breakLength,
        public array $teachers,
        public ?int $statusId,
        public ?int $classroomId,
        public LessonCheckResponse $attendance,
        public LessonCheckResponse $homework,
        public bool $isVisible,
        public ?OnlineLessonResponse $onlineLesson,
        public string $createdAt,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $online = ResponseValue::nullableObject($payload, 'onlineLesson');

        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::string($payload, 'startAt'),
            ResponseValue::string($payload, 'endAt'),
            ResponseValue::int($payload, 'length'),
            ResponseValue::nullableInt($payload, 'breakLength'),
            ResponseValue::integers($payload, 'teachers'),
            ResponseValue::nullableInt($payload, 'statusId'),
            ResponseValue::nullableInt($payload, 'classroomId'),
            LessonCheckResponse::fromArray(ResponseValue::object($payload, 'attendance')),
            LessonCheckResponse::fromArray(ResponseValue::object($payload, 'homework')),
            ResponseValue::bool($payload, 'isVisible'),
            $online === null ? null : OnlineLessonResponse::fromArray($online),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}
