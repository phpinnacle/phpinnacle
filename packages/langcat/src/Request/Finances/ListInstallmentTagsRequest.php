<?php

namespace PHPinnacle\Langcat\Request\Finances;

use PHPinnacle\Langcat\Enum\InstallmentTagType;
use PHPinnacle\Langcat\Support\RequestValue;

final class ListInstallmentTagsRequest
{
    use FinanceListFields;

    public static function make(): self
    {
        return new self;
    }

    public function createdAfter(string $value): self
    {
        return $this->dateFilter('createdAt_gt', $value);
    }

    public function createdAt(string $value): self
    {
        return $this->dateFilter('createdAt', $value);
    }

    public function createdAtOrAfter(string $value): self
    {
        return $this->dateFilter('createdAt_ge', $value);
    }

    public function createdAtOrBefore(string $value): self
    {
        return $this->dateFilter('createdAt_le', $value);
    }

    public function createdBefore(string $value): self
    {
        return $this->dateFilter('createdAt_lt', $value);
    }

    public function name(string $value): self
    {
        return $this->set('name', RequestValue::nonEmpty($value, 'Tag name'));
    }

    public function sortBy(string $value): self
    {
        return $this->sort($value, ['+id', '-id', '+name', '-name', '+createdAt', '-createdAt']);
    }

    public function type(InstallmentTagType $value): self
    {
        return $this->set('type', $value->value);
    }
}
