<?php

namespace PHPinnacle\Langcat\Request\Groups;

use LogicException;
use PHPinnacle\Langcat\Enum\AssignType;

final class ChangeStudentAssignTypeRequest
{
    private ?AssignType $assignType = null;

    public static function make(): self
    {
        return new self;
    }

    public function assignType(AssignType $type): self
    {
        $this->assignType = $type;

        return $this;
    }

    /** @return array{assignType: string} */
    public function toArray(): array
    {
        return ['assignType' => ($this->assignType ?? throw new LogicException('Assignment type is required.'))->value];
    }
}
