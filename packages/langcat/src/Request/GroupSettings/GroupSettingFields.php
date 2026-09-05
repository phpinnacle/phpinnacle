<?php

namespace PHPinnacle\Langcat\Request\GroupSettings;

use InvalidArgumentException;
use PHPinnacle\Langcat\Enum\OnlineLessonProvider;
use PHPinnacle\Langcat\Enum\Weekday;
use PHPinnacle\Langcat\Support\RequestValue;

/** @internal */
trait GroupSettingFields
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function subjectId(int $value): self
    {
        return $this->set('subjectId', RequestValue::positive($value, 'Subject ID'));
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
        return $this->set('classroomId', RequestValue::positive($value, 'Classroom ID'));
    }

    public function day(Weekday $value): self
    {
        return $this->set('day', $value->value);
    }

    public function startTime(string $value): self
    {
        return $this->set('startTime', RequestValue::time($value, 'Start time'));
    }

    public function lessonLength(int $value): self
    {
        return $this->set('lessonLength', RequestValue::positive($value, 'Lesson length'));
    }

    public function breakLength(int $value): self
    {
        return $this->set('breakLength', RequestValue::nonNegative($value, 'Break length'));
    }

    public function onlineLesson(bool $enabled): self
    {
        return $this->set('isOnlineLessonEnabled', $enabled);
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

    private function set(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }
}
