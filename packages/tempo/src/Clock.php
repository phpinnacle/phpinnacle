<?php

namespace PHPinnacle\Tempo;

use DateTimeImmutable;

class Clock
{
    private static ?DateTimeImmutable $time = null;

    public static function date(): DateTimeImmutable
    {
        return self::now()->setTime(0, 0);
    }

    public static function now(): DateTimeImmutable
    {
        return self::$time ?? new DateTimeImmutable;
    }

    public static function rewind(?DateTimeImmutable $time = null): ?DateTimeImmutable
    {
        return self::$time = $time;
    }

    public static function unix(): int
    {
        return self::now()->getTimestamp();
    }

    public static function year(): DateTimeImmutable
    {
        $now = self::now();

        return $now->setDate((int) $now->format('Y'), 1, 1)->setTime(0, 0);
    }
}
