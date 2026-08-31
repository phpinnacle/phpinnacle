<?php

namespace PHPinnacle\Tempo\Calendar;

use Carbon\CarbonImmutable;

final readonly class RecurringEvent
{
    public function __construct(
        public string $rule,
        public CarbonImmutable $start,
        public ?CarbonImmutable $end = null,
    ) {}

    public function durationInSeconds(): int
    {
        if ($this->end === null || $this->end->lessThan($this->start)) {
            return 0;
        }

        return (int) $this->start->diffInSeconds($this->end);
    }
}
