<?php

namespace PHPinnacle\Langcat\Request\Finances;

final class ListCollectionInstallmentsRequest
{
    use FinanceListFields;

    public static function make(): self
    {
        return new self;
    }

    public function sortBy(string $value): self
    {
        return $this->sort($value, ['+id', '-id', '+dateTo', '-dateTo', '+createdAt', '-createdAt']);
    }

    public function createdAt(string $value): self
    {
        return $this->dateFilter('createdAt', $value);
    }

    public function createdAfter(string $value): self
    {
        return $this->dateFilter('createdAt_gt', $value);
    }

    public function createdAtOrAfter(string $value): self
    {
        return $this->dateFilter('createdAt_ge', $value);
    }

    public function createdBefore(string $value): self
    {
        return $this->dateFilter('createdAt_lt', $value);
    }

    public function createdAtOrBefore(string $value): self
    {
        return $this->dateFilter('createdAt_le', $value);
    }
}
