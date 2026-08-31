<?php

namespace PHPinnacle\Langcat\Response\GroupSettings;

use PHPinnacle\Langcat\Enum\Weekday;
use PHPinnacle\Langcat\Response\Groups\OnlineLessonResponse;
use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class GroupSettingResponse
{
    /** @param list<GroupSettingTeacherResponse> $teachers */
    public function __construct(
        public int $id,
        public int $subjectId,
        public array $teachers,
        public int $classroomId,
        public Weekday $day,
        public string $startTime,
        public int $lessonLength,
        public int $breakLength,
        public bool $isOnlineLessonEnabled,
        public ?OnlineLessonResponse $onlineLessonProvider,
        public string $createdAt,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $online = ResponseValue::nullableObject($payload, 'onlineLessonProvider');

        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::int($payload, 'subjectId'),
            ResponseValue::objects($payload, 'teachers', GroupSettingTeacherResponse::fromArray(...)),
            ResponseValue::int($payload, 'classroomId'),
            Weekday::from(ResponseValue::string($payload, 'day')),
            ResponseValue::string($payload, 'startTime'),
            ResponseValue::int($payload, 'lessonLength'),
            ResponseValue::int($payload, 'breakLength'),
            ResponseValue::bool($payload, 'isOnlineLessonEnabled'),
            $online === null ? null : OnlineLessonResponse::fromArray($online),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}
