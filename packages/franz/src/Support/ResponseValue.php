<?php

namespace PHPinnacle\Franz\Support;

use UnexpectedValueException;

/** @internal */
final readonly class ResponseValue
{
    /** @return ($nullable is true ? array<array-key, mixed>|null : array<array-key, mixed>) */
    public static function object(mixed $value, bool $nullable = false): ?array
    {
        return is_array($value) || $nullable && $value === null
            ? $value
            : throw new UnexpectedValueException('Expected a response object.');
    }

    /** @return ($nullable is true ? list<array<array-key, mixed>>|null : list<array<array-key, mixed>>) */
    public static function objects(mixed $value, bool $nullable = false): ?array
    {
        return $nullable && $value === null ? null : array_map(self::object(...), self::items($value));
    }

    /** @return ($nullable is true ? list<mixed>|null : list<mixed>) */
    public static function items(mixed $value, bool $nullable = false): ?array
    {
        return is_array($value) && array_is_list($value) || $nullable && $value === null
            ? $value
            : throw new UnexpectedValueException('Expected a response list.');
    }

    /** @return ($nullable is true ? string|null : string) */
    public static function string(mixed $value, bool $nullable = false): ?string
    {
        return is_string($value) || $nullable && $value === null
            ? $value
            : throw new UnexpectedValueException('Expected a response string.');
    }

    /** @return ($nullable is true ? bool|null : bool) */
    public static function bool(mixed $value, bool $nullable = false): ?bool
    {
        return is_bool($value) || $nullable && $value === null
            ? $value
            : throw new UnexpectedValueException('Expected a response boolean.');
    }

    /** @return ($nullable is true ? int|null : int) */
    public static function int(mixed $value, bool $nullable = false): ?int
    {
        return is_int($value) || $nullable && $value === null
            ? $value
            : throw new UnexpectedValueException('Expected a response integer.');
    }

    /** @return ($nullable is true ? list<string>|null : list<string>) */
    public static function strings(mixed $value, bool $nullable = false): ?array
    {
        return $nullable && $value === null ? null : array_map(self::string(...), self::items($value));
    }

    /** @return ($nullable is true ? array<string, string>|null : array<string, string>) */
    public static function config(mixed $value, bool $nullable = false): ?array
    {
        if ($nullable && $value === null) {
            return null;
        }

        $result = [];

        foreach (self::object($value) as $key => $item) {
            $result[self::string($key)] = self::string($item);
        }

        return $result;
    }
}
