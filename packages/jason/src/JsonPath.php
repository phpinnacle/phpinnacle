<?php

namespace PHPinnacle\Jason;

use InvalidArgumentException;
use JsonException;

final class JsonPath
{
    /** @param list<string|int|null> $segments */
    public static function fromSegments(array $segments): string
    {
        return '$' . implode('', array_map(fn ($segment) => match (true) {
            $segment === null => '[*]',
            is_int($segment) => '[' . $segment . ']',
            default => self::property($segment),
        }, $segments));
    }

    public static function property(string $name): string
    {
        $encoded = json_encode(
            $name,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS,
        );

        return "['" . str_replace(['\\"', "'"], ['"', "\\'"], substr($encoded, 1, -1)) . "']";
    }

    /** @return list<string|int|null> */
    public static function parse(string $path): array
    {
        if (!str_starts_with($path, '$')) {
            throw new InvalidArgumentException('A JSONPath must start with $.');
        }

        $segments = [];
        $offset = 1;

        while ($offset < strlen($path)) {
            if (!preg_match(
                '/\G\[(?:\x27((?:[^\x27\\\\]|\\\\.)*)\x27|(0|[1-9][0-9]*)|(\*))\]/s',
                $path,
                $token,
                PREG_UNMATCHED_AS_NULL,
                $offset,
            )) {
                throw new InvalidArgumentException(
                    'Use bracketed property names, nonnegative indexes, or [*] in a JSONPath.',
                );
            }

            if ($token[1] !== null) {
                $encoded = preg_replace_callback(
                    '/\\\\.|"/s',
                    fn (array $match) => match ($match[0]) {
                        "\\'" => "'",
                        '"' => '\\"',
                        default => $match[0],
                    },
                    $token[1],
                );

                try {
                    $property = json_decode('"' . $encoded . '"', flags: JSON_THROW_ON_ERROR);
                } catch (JsonException $error) {
                    throw new InvalidArgumentException(
                        'Invalid escape sequence in a JSONPath property name.',
                        previous: $error,
                    );
                }

                if (!is_string($property)) {
                    throw new InvalidArgumentException('A JSONPath property name must be a string.');
                }

                $segments[] = $property;
            } else {
                $segments[] = $token[2] !== null ? (int) $token[2] : null;
            }

            $offset += strlen($token[0]);
        }

        if (self::fromSegments($segments) !== $path) {
            throw new InvalidArgumentException('Use the canonical bracket notation for JSONPath.');
        }

        return $segments;
    }
}
