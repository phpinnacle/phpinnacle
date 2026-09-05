<?php

namespace PHPinnacle\Langcat\Request\Finances;

use PHPinnacle\Langcat\Support\RequestValue;

final class ListInstallmentCollectionsRequest
{
    use FinanceListFields;

    public static function make(): self
    {
        return new self;
    }

    public function sortBy(string $value): self
    {
        return $this->sort($value, [
            '+id',
            '-id',
            '+name',
            '-name',
            '+isShared',
            '-isShared',
            '+createdAt',
            '-createdAt',
        ]);
    }

    public function schoolId(int $value): self
    {
        return $this->set('schoolId', RequestValue::positive($value, 'School ID'));
    }
}
