<?php

namespace PHPinnacle\Langcat\Request\Finances;

use LogicException;
use PHPinnacle\Langcat\Support\RequestValue;

final class WriteStudentInstallmentRequest
{
    /** @var array<string, int|float|string> */
    private array $data = [];

    public static function make(): self
    {
        return new self;
    }

    public function value(int|float $value): self
    {
        $this->data['value'] = RequestValue::nonNegative($value, 'Installment value');

        return $this;
    }

    public function dueDate(string $value): self
    {
        $this->data['dueDate'] = RequestValue::date($value, 'Due date', false);

        return $this;
    }

    /** @return array<string, int|float|string> */
    public function toArray(): array
    {
        foreach (['value', 'dueDate'] as $field) {
            if (!array_key_exists($field, $this->data)) {
                throw new LogicException("Student installment field [{$field}] is required.");
            }
        }

        return $this->data;
    }
}
