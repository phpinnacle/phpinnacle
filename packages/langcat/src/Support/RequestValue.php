<?php

namespace PHPinnacle\Langcat\Support;

use DateTimeImmutable;
use InvalidArgumentException;

/** @internal */
final readonly class RequestValue
{
    public static function positive(int $value, string $name): int
    {
        if ($value < 1) {
            throw new InvalidArgumentException("{$name} must be positive.");
        }

        return $value;
    }

    public static function nonNegative(int|float $value, string $name): int|float
    {
        if ($value < 0) {
            throw new InvalidArgumentException("{$name} cannot be negative.");
        }

        return $value;
    }

    public static function nonNegativeInt(int $value, string $name): int
    {
        if ($value < 0) {
            throw new InvalidArgumentException("{$name} cannot be negative.");
        }

        return $value;
    }

    public static function nonEmpty(string $value, string $name): string
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException("{$name} cannot be empty.");
        }

        return $value;
    }

    public static function date(string $value, string $name, bool $withTime = true): string
    {
        $formats = $withTime ? ['Y-m-d', 'Y-m-d H:i:s', DATE_ATOM] : ['Y-m-d'];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format === DATE_ATOM ? $format : '!' . $format, $value);

            if ($date !== false && $date->format($format) === $value) {
                return $value;
            }
        }

        throw new InvalidArgumentException("{$name} has an invalid date format.");
    }

    public static function time(string $value, string $name): string
    {
        $time = DateTimeImmutable::createFromFormat('!H:i', $value);

        if ($time === false || $time->format('H:i') !== $value) {
            throw new InvalidArgumentException("{$name} must use H:i format.");
        }

        return $value;
    }

    public static function httpUrl(string $value, string $name): string
    {
        $scheme = parse_url($value, PHP_URL_SCHEME);

        if (filter_var($value, FILTER_VALIDATE_URL) === false || !in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException("{$name} must be a valid HTTP or HTTPS URL.");
        }

        return $value;
    }
}
