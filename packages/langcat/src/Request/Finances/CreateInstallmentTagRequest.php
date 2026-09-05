<?php

namespace PHPinnacle\Langcat\Request\Finances;

use LogicException;
use PHPinnacle\Langcat\Enum\InstallmentTagType;
use PHPinnacle\Langcat\Support\RequestValue;

final class CreateInstallmentTagRequest
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

    public function type(InstallmentTagType $value): self
    {
        $this->data['type'] = $value->value;

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
        foreach (['name', 'type'] as $field) {
            if (!array_key_exists($field, $this->data)) {
                throw new LogicException("Installment tag field [{$field}] is required.");
            }
        }

        return $this->data;
    }
}
