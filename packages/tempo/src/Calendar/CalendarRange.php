<?php

namespace PHPinnacle\Tempo\Calendar;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;

final readonly class CalendarRange
{
    private function __construct(
        public string $start,
        public string $end,
        private CarbonImmutable $startDate,
        private CarbonImmutable $endDate,
    ) {}

    public static function create(string $start, string $end): ?self
    {
        try {
            $rangeStart = CarbonImmutable::parse($start);
            $rangeEnd = CarbonImmutable::parse($end);
        } catch (InvalidFormatException) {
            return null;
        }

        if ($rangeEnd->lessThanOrEqualTo($rangeStart) || $rangeStart->diffInDays($rangeEnd) > 370) {
            return null;
        }

        return new self($start, $end, $rangeStart, $rangeEnd);
    }

    public function endDate(): CarbonImmutable
    {
        return $this->endDate;
    }

    public function startDate(): CarbonImmutable
    {
        return $this->startDate;
    }
}
