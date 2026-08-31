<?php

namespace PHPinnacle\Langcat\Request\Finances;

final class ListStudentInstallmentsRequest
{
    use FinanceListFields;

    public static function make(): self
    {
        return new self;
    }

    public function sortBy(string $value): self
    {
        return $this->sort($value, ['+id', '-id', '+dueDate', '-dueDate', '+createdAt', '-createdAt']);
    }
}
