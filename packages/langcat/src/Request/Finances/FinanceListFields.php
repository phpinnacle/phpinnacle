<?php

namespace PHPinnacle\Langcat\Request\Finances;

use InvalidArgumentException;
use PHPinnacle\Langcat\Support\RequestValue;

/** @internal */
trait FinanceListFields
{
    /** @var array<string, int|string> */
    private array $query = [];

    public function page(int $value): self
    {
        return $this->set('page', RequestValue::positive($value, 'Page'));
    }

    public function perPage(int $value): self
    {
        if ($value > 100) {
            throw new InvalidArgumentException('Items per page cannot exceed 100.');
        }

        return $this->set('perPage', RequestValue::positive($value, 'Items per page'));
    }

    /** @return array<string, int|string> */
    public function toQuery(): array
    {
        return $this->query;
    }

    private function set(string $key, int|string $value): self
    {
        $this->query[$key] = $value;

        return $this;
    }

    /** @param list<string> $allowed */
    private function sort(string $value, array $allowed): self
    {
        if (!in_array($value, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported installment sort.');
        }

        return $this->set('sortBy', $value);
    }

    private function dateFilter(string $key, string $value): self
    {
        return $this->set($key, RequestValue::date($value, 'Created at'));
    }
}
