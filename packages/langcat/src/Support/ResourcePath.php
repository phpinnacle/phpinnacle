<?php

namespace PHPinnacle\Langcat\Support;

use InvalidArgumentException;

/** @internal */
final readonly class ResourcePath
{
    public static function id(string $resource, int $id): string
    {
        if ($id < 1) {
            throw new InvalidArgumentException('Resource ID must be positive.');
        }

        return '/' . $resource . '/' . $id;
    }
}
