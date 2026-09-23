<?php

namespace PHPinnacle\Tempo\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidDuration implements ValidationRule
{
    public const int MAX_SECONDS = 9_007_199_254_740_991;

    private const array UNIT_SECONDS = [
        'd' => 86_400,
        'h' => 3600,
        'm' => 60,
        's' => 1,
    ];

    /**
     * @param 'seconds'|'string' $format
     * @param non-empty-list<'day'|'hour'|'minute'|'second'> $units
     */
    public function __construct(
        private readonly string $format,
        private readonly array $units = ['day', 'hour', 'minute', 'second'],
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $valid = $this->format === 'seconds'
            ? self::validSeconds($value) && ($value % $this->smallestUnitSeconds()) === 0
            : self::parseString($value, $this->units) !== null;

        if (!$valid) {
            $fail('phpinnacle-tempo::duration.invalid')->translate();
        }
    }

    public static function validSeconds(mixed $value): bool
    {
        return is_int($value) && $value >= 0 && $value <= self::MAX_SECONDS;
    }

    /** @param non-empty-list<'day'|'hour'|'minute'|'second'> $units */
    public static function parseString(mixed $value, array $units = ['day', 'hour', 'minute', 'second']): ?int
    {
        if (!is_string($value) || preg_match('/^(?:(?:0|[1-9]\d*)[dhms]\s*)+$/iD', trim($value)) !== 1) {
            return null;
        }

        preg_match_all('/(\d+)([dhms])/i', $value, $matches, PREG_SET_ORDER);

        $total = 0;
        $suffixes = array_map(static fn (string $unit) => $unit[0], $units);

        foreach ($matches as $match) {
            if (!in_array(strtolower($match[2]), $suffixes, true)) {
                return null;
            }

            $amount = filter_var($match[1], FILTER_VALIDATE_INT);
            $seconds = self::UNIT_SECONDS[strtolower($match[2])];

            if ($amount === false || $amount > intdiv(self::MAX_SECONDS - $total, $seconds)) {
                return null;
            }

            $total += $amount * $seconds;
        }

        return $total;
    }

    private function smallestUnitSeconds(): int
    {
        return min(array_map(static fn (string $unit) => self::UNIT_SECONDS[$unit[0]], $this->units));
    }
}
