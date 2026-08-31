<?php

namespace PHPinnacle\Tempo\Calendar;

use Carbon\CarbonImmutable;

final readonly class CalendarOccurrence
{
    public function __construct(
        public CarbonImmutable $start,
        public ?CarbonImmutable $end = null,
    ) {}
}
