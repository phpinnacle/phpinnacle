<?php

namespace PHPinnacle\Langcat\Request\Finances;

use LogicException;
use PHPinnacle\Langcat\Support\RequestValue;

final class CreateInstallmentRequest
{
    /** @var array<string, int|float|string> */
    private array $data = [];

    public static function make(): self
    {
        return new self;
    }

    public function dateTo(string $value): self
    {
        $this->data['dateTo'] = RequestValue::date($value, 'Installment date');

        return $this;
    }

    /** @return array<string, int|float|string> */
    public function toArray(): array
    {
        if (!array_key_exists('value', $this->data)) {
            throw new LogicException('Installment value is required.');
        }

        return $this->data;
    }

    public function value(int|float $value): self
    {
        $this->data['value'] = RequestValue::nonNegative($value, 'Installment value');

        return $this;
    }
}
