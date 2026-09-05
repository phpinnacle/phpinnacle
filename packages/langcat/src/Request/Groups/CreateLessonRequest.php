<?php

namespace PHPinnacle\Langcat\Request\Groups;

use InvalidArgumentException;
use LogicException;
use PHPinnacle\Langcat\Enum\OnlineLessonProvider;
use PHPinnacle\Langcat\Support\RequestValue;

final class CreateLessonRequest
{
    /** @var array<string, mixed> */
    private array $data = [];

    public static function make(): self
    {
        return new self;
    }

    public function breakLength(int $minutes): self
    {
        return $this->set('breakLength', RequestValue::nonNegative($minutes, 'Break length'));
    }

    public function classroomId(int $id): self
    {
        return $this->set('classroomId', RequestValue::positive($id, 'Classroom ID'));
    }

    public function date(string $date): self
    {
        return $this->set('date', RequestValue::date($date, 'Lesson date', false));
    }

    public function lessonLength(int $minutes): self
    {
        return $this->set('lessonLength', RequestValue::positive($minutes, 'Lesson length'));
    }

    public function online(bool $enabled = true): self
    {
        return $this->set('isOnlineLessonEnabled', $enabled);
    }

    public function onlineProvider(OnlineLessonProvider $provider): self
    {
        return $this->set('onlineLessonProvider', $provider->value);
    }

    public function onlineUrl(string $url): self
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('Online lesson URL must be valid.');
        }

        return $this->set('onlineLessonProviderUrl', $url);
    }

    public function settingId(int $id): self
    {
        return $this->set('settingId', RequestValue::positive($id, 'Setting ID'));
    }

    public function startTime(string $time): self
    {
        return $this->set('startTime', RequestValue::time($time, 'Lesson start time'));
    }

    public function teachers(LessonTeacherRequest ...$teachers): self
    {
        return $this->set('teachers', array_map(
            static fn (LessonTeacherRequest $teacher) => $teacher->toArray(),
            $teachers,
        ));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        foreach (['settingId', 'date', 'startTime', 'lessonLength'] as $field) {
            if (!array_key_exists($field, $this->data)) {
                throw new LogicException("Lesson field [{$field}] is required.");
            }
        }

        if (
            ($this->data['onlineLessonProvider'] ?? null) === OnlineLessonProvider::MeetingLink->value
            && ($this->data['onlineLessonProviderUrl'] ?? null) === null
        ) {
            throw new LogicException('MeetingLink lessons require an online URL.');
        }

        return $this->data;
    }

    private function set(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }
}
