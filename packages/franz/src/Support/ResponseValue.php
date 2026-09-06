<?php

namespace PHPinnacle\Franz\Support;

use UnexpectedValueException;

/** @internal */
final readonly class ResponseValue
{
    /** @return array<array-key, mixed> */
    public static function object(mixed $value): array
    {
        return is_array($value) ? $value : throw new UnexpectedValueException('Expected a response object.');
    }

    /** @return list<array<array-key, mixed>> */
    public static function objects(mixed $value): array
    {
        return array_map(self::object(...), self::items($value));
    }

    /** @return list<mixed> */
    public static function items(mixed $value): array
    {
        return is_array($value) && array_is_list($value)
            ? $value
            : throw new UnexpectedValueException('Expected a response list.');
    }

    public static function string(mixed $value): string
    {
        return is_string($value) ? $value : throw new UnexpectedValueException('Expected a response string.');
    }

    public static function bool(mixed $value): bool
    {
        return is_bool($value) ? $value : throw new UnexpectedValueException('Expected a response boolean.');
    }

    public static function int(mixed $value): int
    {
        return is_int($value) ? $value : throw new UnexpectedValueException('Expected a response integer.');
    }

    /** @return list<string> */
    public static function strings(mixed $value): array
    {
        return array_map(self::string(...), self::items($value));
    }

    /** @return array<string, string> */
    public static function config(mixed $value): array
    {
        $result = [];

        foreach (self::object($value) as $key => $item) {
            $result[self::string($key)] = self::string($item);
        }

        return $result;
    }
}
