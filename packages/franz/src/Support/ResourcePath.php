<?php

namespace PHPinnacle\Franz\Support;

use InvalidArgumentException;

/** @internal */
final readonly class ResourcePath
{
    public static function segment(string $value): string
    {
        if ($value === '' || $value === '.' || $value === '..') {
            throw new InvalidArgumentException('Resource name must be non-empty and cannot be a dot segment.');
        }

        return rawurlencode($value);
    }
}
