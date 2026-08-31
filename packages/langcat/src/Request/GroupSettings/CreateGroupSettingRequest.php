<?php

namespace PHPinnacle\Langcat\Request\GroupSettings;

use LogicException;

final class CreateGroupSettingRequest
{
    use GroupSettingFields;

    public static function make(): self
    {
        return new self;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        foreach (['subjectId', 'day', 'startTime', 'lessonLength'] as $field) {
            if (!array_key_exists($field, $this->data)) {
                throw new LogicException("Group setting field [{$field}] is required.");
            }
        }

        return $this->data;
    }
}
