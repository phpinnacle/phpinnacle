<?php

use Carbon\CarbonImmutable;
use PHPinnacle\Tempo\Calendar\CalendarRange;
use PHPinnacle\Tempo\Calendar\RecurrenceExpander;
use PHPinnacle\Tempo\Calendar\RecurringEvent;

it('exposes parsed calendar range boundaries', function () {
    $range = CalendarRange::create('2026-07-01T10:15:00+00:00', '2026-07-02T11:30:00+00:00');

    expect($range)
        ->not
        ->toBeNull()
        ->and($range->startDate()->toIso8601String())
        ->toBe('2026-07-01T10:15:00+00:00')
        ->and($range->endDate()->toIso8601String())
        ->toBe('2026-07-02T11:30:00+00:00');
});

it('expands a yearly recurrence into the visible range', function () {
    $range = CalendarRange::create('2026-07-01', '2026-08-01');

    expect($range)->not->toBeNull();

    $occurrences = new RecurrenceExpander()->expand(
        new RecurringEvent('FREQ=YEARLY', CarbonImmutable::parse('1990-07-15')),
        $range,
        10,
    );

    expect($occurrences)
        ->toHaveCount(1)
        ->and($occurrences[0]->start->toDateString())
        ->toBe('2026-07-15')
        ->and($occurrences[0]->end)
        ->toBeNull();
});

it('preserves duration and includes occurrences overlapping the range start', function () {
    $range = CalendarRange::create('2026-07-06 10:30:00', '2026-07-13 00:00:00');

    expect($range)->not->toBeNull();

    $occurrences = new RecurrenceExpander()->expand(
        new RecurringEvent(
            rule: 'FREQ=WEEKLY;BYDAY=MO,WE',
            start: CarbonImmutable::parse('2026-07-06 10:00:00'),
            end: CarbonImmutable::parse('2026-07-06 11:30:00'),
        ),
        $range,
        10,
    );

    expect($occurrences)
        ->toHaveCount(2)
        ->and($occurrences[0]->start->format('Y-m-d H:i'))
        ->toBe('2026-07-06 10:00')
        ->and($occurrences[0]->end?->format('Y-m-d H:i'))
        ->toBe('2026-07-06 11:30')
        ->and($occurrences[1]->start->format('Y-m-d H:i'))
        ->toBe('2026-07-08 10:00');
});

it('honors the expansion limit', function () {
    $range = CalendarRange::create('2026-07-01', '2026-08-01');

    expect($range)
        ->not
        ->toBeNull()
        ->and(new RecurrenceExpander()->expand(
            new RecurringEvent('FREQ=DAILY', CarbonImmutable::parse('2026-07-01')),
            $range,
            2,
        ))
        ->toHaveCount(2);
});

it('rejects invalid recurrence rules', function () {
    $range = CalendarRange::create('2026-07-01', '2026-08-01');

    expect($range)
        ->not
        ->toBeNull()
        ->and(fn () => new RecurrenceExpander()->expand(
            new RecurringEvent('NOT AN RRULE', CarbonImmutable::parse('2026-07-10')),
            $range,
            10,
        ))
        ->toThrow(InvalidArgumentException::class);
});
