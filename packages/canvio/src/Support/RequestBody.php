<?php

namespace PHPinnacle\Canvio\Support;

use DateTimeInterface;

/** @internal */
final readonly class RequestBody
{
    public static function date(?DateTimeInterface $date): ?string
    {
        return $date?->format(DATE_ATOM);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    public static function withoutNulls(array $fields): array
    {
        return array_filter($fields, static fn (mixed $value) => $value !== null);
    }
}
