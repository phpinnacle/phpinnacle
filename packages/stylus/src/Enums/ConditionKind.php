<?php

namespace PHPinnacle\Stylus\Enums;

enum ConditionKind: string
{
    case Truthy = 'truthy';
    case Comparison = 'comparison';
    case Test = 'test';

    public function key(string $name): string
    {
        return $this === self::Truthy ? $this->value : $this->prefix() . $name;
    }

    public function prefix(): string
    {
        return $this->value . ':';
    }
}
