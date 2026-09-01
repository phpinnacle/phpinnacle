<?php

namespace PHPinnacle\Canvio\Support;

use InvalidArgumentException;

/** @internal */
final readonly class ResourcePath
{
    public static function segment(int|string $value, string $name = 'Resource ID'): string
    {
        if (is_int($value) && $value < 1) {
            throw new InvalidArgumentException("{$name} must be positive.");
        }

        if (is_string($value) && trim($value) === '') {
            throw new InvalidArgumentException("{$name} cannot be empty.");
        }

        return rawurlencode((string) $value);
    }
}
