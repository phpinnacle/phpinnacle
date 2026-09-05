<?php

use PHPinnacle\Tempo\Clock;

afterEach(function () {
    Clock::rewind();
});

it('provides a controllable clock', function () {
    Clock::rewind(new DateTimeImmutable('2026-07-17 12:34:56 UTC'));

    expect(Clock::now()->format(DATE_ATOM))
        ->toBe('2026-07-17T12:34:56+00:00')
        ->and(Clock::date()->format(DATE_ATOM))
        ->toBe('2026-07-17T00:00:00+00:00')
        ->and(Clock::unix())
        ->toBe(1_784_291_696)
        ->and(Clock::year()->format(DATE_ATOM))
        ->toBe('2026-01-01T00:00:00+00:00');
});
