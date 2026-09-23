<?php

namespace PHPinnacle\Tempo\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidCronExpression implements ValidationRule
{
    private const int DAY_OF_MONTH = 2;

    private const int MONTH = 3;

    private const int DAY_OF_WEEK = 4;

    private const array RANGES = [
        [0, 59],
        [0, 23],
        [1, 31],
        [1, 12],
        [0, 7],
    ];

    private const array MONTHS = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

    private const array WEEKDAYS = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!self::isValid($value)) {
            $fail('phpinnacle-tempo::cron.invalid')->translate();
        }
    }

    public static function isValid(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $fields = preg_split('/\s+/', trim($value));

        if ($fields === false || count($fields) !== 5) {
            return false;
        }

        if ($fields[self::DAY_OF_MONTH] === '?' && $fields[self::DAY_OF_WEEK] === '?') {
            return false;
        }

        foreach ($fields as $index => $field) {
            if ($field === '?' && ($index === self::DAY_OF_MONTH || $index === self::DAY_OF_WEEK)) {
                continue;
            }

            if ($field === 'L' && $index === self::DAY_OF_MONTH) {
                continue;
            }

            [$minimum, $maximum] = self::RANGES[$index];

            foreach (explode(',', $field) as $segment) {
                if (!self::validSegment($segment, $minimum, $maximum, $index)) {
                    return false;
                }
            }
        }

        return true;
    }

    private static function validSegment(string $segment, int $minimum, int $maximum, int $index): bool
    {
        if (!preg_match('/^(\*|\d+|[a-z]{3})(?:-(\d+|[a-z]{3}))?(?:\/(\d+))?$/iD', $segment, $matches)) {
            return false;
        }

        $start = $matches[1];
        $end = $matches[2] ?? null;
        $step = $matches[3] ?? null;

        if ($start === '*' && $end !== null && $end !== '') {
            return false;
        }

        if ($start !== '*' && !self::inRange($start, $minimum, $maximum, $index)) {
            return false;
        }

        if ($end !== null && $end !== '') {
            if (
                !self::inRange($end, $minimum, $maximum, $index)
                || self::fieldNumber($end, $index) < self::fieldNumber($start, $index)
            ) {
                return false;
            }
        }

        if ($step === null) {
            return true;
        }

        return ($start === '*' || $end !== null && $end !== '') && preg_match('/^[1-9]\d*$/D', $step) === 1;
    }

    private static function inRange(string $value, int $minimum, int $maximum, int $index): bool
    {
        $number = self::fieldNumber($value, $index);

        return (
            $number !== null
            && (ctype_digit($value) ? strlen($value) <= 2 : strlen($value) === 3)
            && $number >= $minimum
            && $number <= $maximum
        );
    }

    private static function fieldNumber(string $value, int $index): ?int
    {
        if (ctype_digit($value)) {
            return (int) $value;
        }

        $names = match ($index) {
            self::MONTH => self::MONTHS,
            self::DAY_OF_WEEK => self::WEEKDAYS,
            default => [],
        };
        $position = array_search(strtoupper($value), $names, true);

        return $position === false ? null : $position + ($index === self::MONTH ? 1 : 0);
    }
}
