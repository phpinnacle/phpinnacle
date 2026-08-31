<?php

namespace PHPinnacle\Langcat\Request\GroupSettings;

use LogicException;
use PHPinnacle\Langcat\Support\RequestValue;

final class CreateProgramRequest
{
    /** @var array<string, int|string> */
    private array $data = [];

    public static function make(): self
    {
        return new self;
    }

    public function name(string $value): self
    {
        $this->data['name'] = RequestValue::nonEmpty($value, 'Program name');

        return $this;
    }

    public function schoolId(int $value): self
    {
        $this->data['schoolId'] = RequestValue::positive($value, 'School ID');

        return $this;
    }

    /** @return array<string, int|string> */
    public function toArray(): array
    {
        foreach (['schoolId', 'name'] as $field) {
            if (!array_key_exists($field, $this->data)) {
                throw new LogicException("Program field [{$field}] is required.");
            }
        }

        return $this->data;
    }
}
