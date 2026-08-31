<?php

namespace PHPinnacle\Tempo\Contracts;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use PHPinnacle\Tempo\Calendar\CalendarEvent;
use PHPinnacle\Tempo\Calendar\CalendarRange;

interface EventSource extends HasColor, HasIcon, HasLabel
{
    public function getKey(): string;

    /** @return list<CalendarEvent> */
    public function getEvents(CalendarRange $range, int $limit): array;
}
