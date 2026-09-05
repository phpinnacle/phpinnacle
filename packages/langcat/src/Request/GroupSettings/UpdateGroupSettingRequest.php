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
        $this->data['teachers'] = null;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }
}
