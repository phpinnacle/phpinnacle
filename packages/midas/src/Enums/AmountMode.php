<?php

namespace PHPinnacle\Midas\Enums;

use InvalidArgumentException;
use PHPinnacle\Money\Money;

enum AmountMode
{
    case Partial;
    case Full;

    public function accepts(Money $amount, Money $authorized): bool
    {
        return $this === self::Partial || $amount->eq($authorized);
    }

    public function validate(Money $amount, Money $authorized): void
    {
        if (!$this->accepts($amount, $authorized)) {
            throw new InvalidArgumentException('This operation requires a full capture or void of the authorization.');
        }
    }
}
