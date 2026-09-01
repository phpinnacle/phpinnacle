<?php

namespace PHPinnacle\Canvio\Support;

use BackedEnum;
use InvalidArgumentException;
use Stringable;

/** @internal */
final readonly class QueryEncoder
{
    /** @param array<string, mixed> $values */
    public static function encode(array $values): string
    {
        $parts = [];

        foreach ($values as $key => $value) {
            self::append($parts, (string) $key, $value);
        }

        return implode('&', $parts);
    }

    /** @param list<string> $parts */
    private static function append(array &$parts, string $key, mixed $value): void
    {
        if ($value === null) {
            return;
        }

        if (is_array($value)) {
            foreach ($value as $childKey => $childValue) {
                self::append(
                    $parts,
                    array_is_list($value) ? $key . '[]' : $key . '[' . $childKey . ']',
                    $childValue,
                );
            }

            return;
        }

        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if (is_bool($value)) {
            $value = (int) $value;
        }

        if (!is_int($value) && !is_float($value) && !is_string($value)) {
            throw new InvalidArgumentException("Request field [{$key}] must be scalar, an enum, or a nested array.");
        }

        $parts[] = rawurlencode($key) . '=' . rawurlencode((string) $value);
    }
}
