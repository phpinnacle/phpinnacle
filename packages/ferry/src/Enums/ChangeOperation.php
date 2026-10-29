<?php

namespace PHPinnacle\Ferry\Enums;

enum ChangeOperation: string
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';

    /** @param 'c'|'u'|'d'|'r' $op */
    public static function fromDebezium(string $op): ?self
    {
        return match ($op) {
            'c' => self::Create,
            'u' => self::Update,
            'd' => self::Delete,
            'r' => null,
        };
    }
}
