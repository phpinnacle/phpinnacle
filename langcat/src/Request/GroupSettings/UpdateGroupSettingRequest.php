<?php

namespace PHPinnacle\Langcat\Request\GroupSettings;

final class UpdateGroupSettingRequest
{
    use GroupSettingFields;

    public static function make(): self
    {
        return new self;
    }

    public function clearTeachers(): self
    {
        return $this->set('teachers', null);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }
}
