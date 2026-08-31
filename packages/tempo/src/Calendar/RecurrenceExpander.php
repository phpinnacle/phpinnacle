<?php

namespace PHPinnacle\Tempo\Calendar;

use Carbon\CarbonImmutable;
use RRule\RRule;

final class RecurrenceExpander
{
    /** @return list<CalendarOccurrence> */
    public function expand(RecurringEvent $event, CalendarRange $range, int $limit): array
    {
        $duration = $event->durationInSeconds();
        $rangeStart = $range->startDate();
        $rangeEnd = $range->endDate();
        $recurrence = new RRule($event->rule, $event->start->toDateTime());
        $occurrences = $recurrence->getOccurrencesBetween(
            $rangeStart->subSeconds($duration)->toDateTime(),
            $rangeEnd->toDateTime(),
            $limit + 1,
        );
        $expanded = [];

        foreach ($occurrences as $occurrence) {
            $occurrenceStart = CarbonImmutable::instance($occurrence);
            $occurrenceEnd = $duration > 0 ? $occurrenceStart->addSeconds($duration) : null;

            if (
                $occurrenceStart->greaterThanOrEqualTo($rangeEnd)
                || ($occurrenceEnd ?? $occurrenceStart)->lessThanOrEqualTo($rangeStart)
            ) {
                continue;
            }

            $expanded[] = new CalendarOccurrence($occurrenceStart, $occurrenceEnd);

            if (count($expanded) >= $limit) {
                break;
            }
        }

        return $expanded;
    }
}
