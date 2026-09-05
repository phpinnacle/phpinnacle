<?php

namespace PHPinnacle\Langcat\Request\GroupSettings;

use InvalidArgumentException;
use PHPinnacle\Langcat\Enum\OnlineLessonProvider;
use PHPinnacle\Langcat\Enum\Weekday;
use PHPinnacle\Langcat\Support\RequestValue;

/** @internal */
trait GroupSettingFields
{
    /**
     * @var array{
     *     subjectId?: int,
     *     teachers?: list<array{id: int, salary: int|float}>|null,
     *     classroomId?: int,
     *     day?: string,
     *     startTime?: string,
     *     lessonLength?: int,
     *     breakLength?: int|float,
     *     isOnlineLessonEnabled?: bool,
     *     onlineLessonProvider?: string|null,
     *     onlineLessonProviderUrl?: string|null
     * }
     */
    private array $data = [];

    public function subjectId(int $value): self
    {
        $this->data['subjectId'] = RequestValue::positive($value, 'Subject ID');

        return $this;
    }

    public function teacher(int $id, int|float $salary): self
    {
        $this->data['teachers'][] = [
            'id' => RequestValue::positive($id, 'Teacher ID'),
            'salary' => RequestValue::nonNegative($salary, 'Teacher salary'),
        ];

        return $this;
    }

    public function classroomId(int $value): self
    {
        $this->data['classroomId'] = RequestValue::positive($value, 'Classroom ID');

        return $this;
    }

    public function day(Weekday $value): self
    {
        $this->data['day'] = $value->value;

        return $this;
    }

    public function startTime(string $value): self
    {
        $this->data['startTime'] = RequestValue::time($value, 'Start time');

        return $this;
    }

    public function lessonLength(int $value): self
    {
        $this->data['lessonLength'] = RequestValue::positive($value, 'Lesson length');

        return $this;
    }

    public function breakLength(int $value): self
    {
        $this->data['breakLength'] = RequestValue::nonNegative($value, 'Break length');

        return $this;
    }

    public function onlineLesson(bool $enabled): self
    {
        $this->data['isOnlineLessonEnabled'] = $enabled;

        return $this;
    }

    public function onlineLessonProvider(?OnlineLessonProvider $provider, ?string $url = null): self
    {
        if ($provider === OnlineLessonProvider::MeetingLink && $url === null) {
            throw new InvalidArgumentException('MeetingLink requires an online lesson provider URL.');
        }

        $this->data['onlineLessonProvider'] = $provider?->value;
        $this->data['onlineLessonProviderUrl'] = $url === null
            ? null
            : RequestValue::httpUrl($url, 'Online lesson provider URL');

        return $this;
    }
}
