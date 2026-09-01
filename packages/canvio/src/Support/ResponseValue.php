<?php

namespace PHPinnacle\Canvio\Support;

use UnexpectedValueException;

/** @internal */
final readonly class ResponseValue
{
    /** @param array<string, mixed> $payload */
    public static function int(array $payload, string $key): int
    {
        return is_int($payload[$key] ?? null)
            ? $payload[$key]
            : throw new UnexpectedValueException("Response field [{$key}] must be an integer.");
    }

    /** @param array<string, mixed> $payload */
    public static function nullableBool(array $payload, string $key): ?bool
    {
        $value = $payload[$key] ?? null;

        return $value === null || is_bool($value)
            ? $value
            : throw new UnexpectedValueException("Response field [{$key}] must be a boolean or null.");
    }

    /** @param array<string, mixed> $payload */
    public static function nullableInt(array $payload, string $key): ?int
    {
        $value = $payload[$key] ?? null;

        return $value === null || is_int($value)
            ? $value
            : throw new UnexpectedValueException("Response field [{$key}] must be an integer or null.");
    }

    /** @param array<string, mixed> $payload */
    public static function nullableNumber(array $payload, string $key): int|float|null
    {
        $value = $payload[$key] ?? null;

        return $value === null || is_int($value) || is_float($value)
            ? $value
            : throw new UnexpectedValueException("Response field [{$key}] must be numeric or null.");
    }

    /** @param array<string, mixed> $payload */
    public static function nullableObject(array $payload, string $key): ?array
    {
        $value = $payload[$key] ?? null;

        return $value === null || is_array($value)
            ? $value
            : throw new UnexpectedValueException("Response field [{$key}] must be an object or null.");
    }

    /** @param array<string, mixed> $payload */
    public static function nullableString(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        return $value === null || is_string($value)
            ? $value
            : throw new UnexpectedValueException("Response field [{$key}] must be a string or null.");
    }

    /** @param array<string, mixed> $payload */
    public static function string(array $payload, string $key): string
    {
        return is_string($payload[$key] ?? null)
            ? $payload[$key]
            : throw new UnexpectedValueException("Response field [{$key}] must be a string.");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    public static function strings(array $payload, string $key): array
    {
        $values = $payload[$key] ?? [];

        if (!is_array($values) || !array_is_list($values)) {
            throw new UnexpectedValueException("Response field [{$key}] must be a list.");
        }

        foreach ($values as $value) {
            if (!is_string($value)) {
                throw new UnexpectedValueException("Response field [{$key}] must contain only strings.");
            }
        }

        return $values;
    }
}
