<?php

namespace PHPinnacle\Langcat\Support;

use UnexpectedValueException;

/** @internal */
final readonly class ResponseValue
{
    /** @param array<array-key, mixed> $payload */
    public static function int(array $payload, string $key): int
    {
        return is_int($payload[$key] ?? null)
            ? $payload[$key]
            : throw new UnexpectedValueException("Response field [{$key}] must be an integer.");
    }

    /** @param array<array-key, mixed> $payload */
    public static function string(array $payload, string $key): string
    {
        return is_string($payload[$key] ?? null)
            ? $payload[$key]
            : throw new UnexpectedValueException("Response field [{$key}] must be a string.");
    }

    /** @param array<array-key, mixed> $payload */
    public static function nullableString(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        return $value === null || is_string($value)
            ? $value
            : throw new UnexpectedValueException("Response field [{$key}] must be a string or null.");
    }

    /** @param array<array-key, mixed> $payload */
    public static function nullableInt(array $payload, string $key): ?int
    {
        $value = $payload[$key] ?? null;

        return $value === null || is_int($value)
            ? $value
            : throw new UnexpectedValueException("Response field [{$key}] must be an integer or null.");
    }

    /** @param array<array-key, mixed> $payload */
    public static function number(array $payload, string $key): int|float
    {
        $value = $payload[$key] ?? null;

        return is_int($value) || is_float($value)
            ? $value
            : throw new UnexpectedValueException("Response field [{$key}] must be numeric.");
    }

    /** @param array<array-key, mixed> $payload */
    public static function nullableNumber(array $payload, string $key): int|float|null
    {
        $value = $payload[$key] ?? null;

        return $value === null || is_int($value) || is_float($value)
            ? $value
            : throw new UnexpectedValueException("Response field [{$key}] must be numeric or null.");
    }

    /** @param array<array-key, mixed> $payload */
    public static function nullableNumberOrString(array $payload, string $key): int|float|string|null
    {
        $value = $payload[$key] ?? null;

        return $value === null || is_int($value) || is_float($value) || is_string($value)
            ? $value
            : throw new UnexpectedValueException("Response field [{$key}] must be numeric, a string, or null.");
    }

    /** @param array<array-key, mixed> $payload */
    public static function numberOrString(array $payload, string $key): int|float|string
    {
        $value = $payload[$key] ?? null;

        return is_int($value) || is_float($value) || is_string($value)
            ? $value
            : throw new UnexpectedValueException("Response field [{$key}] must be numeric or a string.");
    }

    /** @param array<array-key, mixed> $payload */
    public static function nullableBool(array $payload, string $key): ?bool
    {
        $value = $payload[$key] ?? null;

        return $value === null || is_bool($value)
            ? $value
            : throw new UnexpectedValueException("Response field [{$key}] must be a boolean or null.");
    }

    /** @param array<array-key, mixed> $payload */
    public static function bool(array $payload, string $key): bool
    {
        return is_bool($payload[$key] ?? null)
            ? $payload[$key]
            : throw new UnexpectedValueException("Response field [{$key}] must be a boolean.");
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @return list<string>
     */
    public static function strings(array $payload, string $key): array
    {
        $values = self::list($payload, $key);

        foreach ($values as $value) {
            if (!is_string($value)) {
                throw new UnexpectedValueException("Response field [{$key}] must contain only strings.");
            }
        }

        return $values;
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @return list<int>
     */
    public static function integers(array $payload, string $key): array
    {
        $values = self::list($payload, $key);

        foreach ($values as $value) {
            if (!is_int($value)) {
                throw new UnexpectedValueException("Response field [{$key}] must contain only integers.");
            }
        }

        return $values;
    }

    /**
     * @template T
     *
     * @param  array<array-key, mixed>  $payload
     * @param  callable(array<array-key, mixed>): T  $map
     * @return list<T>
     */
    public static function objects(array $payload, string $key, callable $map): array
    {
        return array_map(
            static fn (mixed $value) => $map(
                is_array($value)
                    ? $value
                    : throw new UnexpectedValueException("Response field [{$key}] must contain only objects."),
            ),
            self::list($payload, $key),
        );
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @return list<int>|null
     */
    public static function nullableIntegers(array $payload, string $key): ?array
    {
        if (!array_key_exists($key, $payload) || $payload[$key] === null) {
            return null;
        }

        return self::integers($payload, $key);
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    public static function object(array $payload, string $key): array
    {
        $value = $payload[$key] ?? null;

        return is_array($value) && ($value === [] || !array_is_list($value))
            ? $value
            : throw new UnexpectedValueException("Response field [{$key}] must be an object.");
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>|null
     */
    public static function nullableObject(array $payload, string $key): ?array
    {
        if (!array_key_exists($key, $payload) || $payload[$key] === null) {
            return null;
        }

        return self::object($payload, $key);
    }

    /** @param array<array-key, mixed> $payload
     * @return list<mixed>
     */
    public static function list(array $payload, string $key): array
    {
        $value = $payload[$key] ?? null;

        return is_array($value) && array_is_list($value)
            ? $value
            : throw new UnexpectedValueException("Response field [{$key}] must be a list.");
    }
}
