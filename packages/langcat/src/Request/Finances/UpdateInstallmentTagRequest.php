<?php

namespace PHPinnacle\Langcat\Request\Finances;

use PHPinnacle\Langcat\Support\RequestValue;

final class UpdateInstallmentTagRequest
{
    /** @var array<string, bool|string> */
    private array $data = [];

    public static function make(): self
    {
        return new self;
    }

    public function name(string $value): self
    {
        $this->data['name'] = RequestValue::nonEmpty($value, 'Tag name');

        return $this;
    }

    public function visibleToStudent(bool $value): self
    {
        $this->data['visibleToStudent'] = $value;

        return $this;
    }

    /** @return array<string, bool|string> */
    public function toArray(): array
    {
        return $this->data;
    }
}
