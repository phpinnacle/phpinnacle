<?php

namespace PHPinnacle\Langcat\Request\GroupSettings;

use PHPinnacle\Langcat\Support\RequestValue;

final class UpdateProgramRequest
{
    /** @var array<string, string> */
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

    /** @return array<string, string> */
    public function toArray(): array
    {
        return $this->data;
    }
}
