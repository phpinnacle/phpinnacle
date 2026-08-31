<?php

namespace PHPinnacle\Money\Contracts;

use PHPinnacle\Money\Money;

interface Formatter
{
    public function inflect(Money $money): array;
}
