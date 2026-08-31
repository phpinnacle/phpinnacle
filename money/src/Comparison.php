<?php

namespace PHPinnacle\Money;

enum Comparison: string
{
    case Equal = 'eq';
    case NotEqual = 'neq';
    case LessThan = 'lt';
    case LessThanOrEqual = 'lte';
    case GreaterThan = 'gt';
    case GreaterThanOrEqual = 'gte';

    public function satisfy(Money $first, Money $second): bool
    {
        return match ($this) {
            self::Equal => $first->eq($second),
            self::NotEqual => !$first->eq($second),
            self::LessThan => $first->lt($second),
            self::LessThanOrEqual => $first->lt($second, true),
            self::GreaterThan => $first->gt($second),
            self::GreaterThanOrEqual => $first->gt($second, true),
        };
    }
}
