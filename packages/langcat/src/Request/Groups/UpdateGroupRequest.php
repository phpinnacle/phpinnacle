<?php

namespace PHPinnacle\Langcat\Request\Groups;

use LogicException;

final class UpdateGroupRequest
{
    use GroupFields;

    public static function make(): self
    {
        return new self;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data !== []
            ? $this->data
            : throw new LogicException('At least one group field must be updated.');
    }
}
