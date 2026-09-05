<?php

namespace PHPinnacle\Langcat\Request\Finances;

use LogicException;
use PHPinnacle\Langcat\Support\RequestValue;

final class CreateInstallmentCollectionRequest
{
    /** @var array<string, int|string> */
    private array $data = [];

    public static function make(): self
    {
        return new self;
    }

    public function schoolId(int $value): self
    {
        $this->data['schoolId'] = RequestValue::positive($value, 'School ID');

        return $this;
    }

    public function name(string $value): self
    {
        $this->data['name'] = RequestValue::nonEmpty($value, 'Collection name');

        return $this;
    }

    /** @return array<string, int|string> */
    public function toArray(): array
    {
        foreach (['schoolId', 'name'] as $field) {
            if (!array_key_exists($field, $this->data)) {
                throw new LogicException("Installment collection field [{$field}] is required.");
            }
        }

        return $this->data;
    }
}
