<?php

namespace PHPinnacle\Money\Contracts;

use PHPinnacle\Money\Money;

interface Formatter
{
    /**
     * @return array<string, string>
     */
    public function inflect(Money $money): array;
}
