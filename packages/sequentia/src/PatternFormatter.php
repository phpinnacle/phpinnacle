<?php

namespace PHPinnacle\Sequentia;

use Illuminate\Support\Str;

final class PatternFormatter
{
    private const string PATTERN = '~\[([a-zA-Z0-9:\*\-]+)\]~';

    public static function format(string $pattern, array $context): string
    {
        return preg_replace_callback(
            self::PATTERN,
            function (array $capture) use ($context) {
                [$match, $placeholder] = $capture;

                $modifiers = explode(':', $placeholder);
                $key = array_shift($modifiers);
                $value = $context[Str::lower($key)] ?? null;

                if ($value === null) {
                    return $match;
                }

                $value = (string) $value;

                foreach ($modifiers as $modifier) {
                    if (is_numeric($modifier)) {
                        $value = Str::substr($value, 0, (int) $modifier);

                        continue;
                    }

                    if (!str_contains($modifier, '-')) {
                        continue;
                    }

                    [$start, $length] = explode('-', $modifier, 2);

                    if (is_numeric($start) && is_numeric($length)) {
                        $value = Str::substr($value, (int) $start, (int) $length);
                    }
                }

                return $key === Str::upper($key) ? Str::upper($value) : $value;
            },
            $pattern,
        );
    }
}
