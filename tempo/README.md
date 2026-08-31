# PHPinnacle Tempo

`phpinnacle/tempo` provides date and time utilities together with custom Filament pickers, date-range filters, and an extensible calendar widget.

## Installation

```bash
composer require phpinnacle/tempo
php artisan filament:assets
```

## Filament fields

```php
use PHPinnacle\Tempo\Forms\DatePicker;
use PHPinnacle\Tempo\Forms\DateRangePicker;
use PHPinnacle\Tempo\Forms\DateTimePicker;
use PHPinnacle\Tempo\Forms\TimePicker;

DatePicker::make('published_on');
DateTimePicker::make('published_at');
DateRangePicker::make('period');
TimePicker::make('starts_at');
```

The fields support custom formats, locale, timezone, minimum and maximum values, and optional automatic closing. The package registers its picker JavaScript and CSS through Filament assets.

## Table filters

```php
use PHPinnacle\Tempo\Filters\DateRangeFilter;
use PHPinnacle\Tempo\Filters\DateTimeRangeFilter;
use PHPinnacle\Tempo\Filters\TimeRangeFilter;

DateRangeFilter::createdAt();
DateRangeFilter::updatedAt();
DateRangeFilter::make('published_at');
DateTimeRangeFilter::make('published_window')
    ->column('published_at');
TimeRangeFilter::make('starts_window')
    ->column('starts_at');
```

The filter name identifies its form state. By default it is also used as the database column; use `column()` when they differ.

## Clock

```php
use PHPinnacle\Tempo\Clock;

Clock::now();
Clock::date();
Clock::unix();
Clock::year();
```

## Calendar widget

`CalendarWidget` delegates event loading to event sources returned by the widget subclass. Each source is also exposed as a filterable event category. The default record action opens the event URL:

```php
use PHPinnacle\Tempo\Contracts\EventSource;
use PHPinnacle\Tempo\Widgets\CalendarWidget;

final class AppointmentsCalendarWidget extends CalendarWidget
{
    /** @return list<EventSource> */
    public function getEventSources(): array
    {
        return [
            new AppointmentsEventSource(),
            new DeadlinesEventSource(),
        ];
    }
}
```

An event source provides its stable filter key, visible label, icon, color, and events for the requested range:

```php
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use PHPinnacle\Tempo\Calendar\CalendarEvent;
use PHPinnacle\Tempo\Calendar\CalendarRange;
use PHPinnacle\Tempo\Contracts\EventSource;

final class AppointmentsEventSource implements EventSource
{
    public function getKey(): string
    {
        return 'appointments';
    }

    public function getLabel(): string
    {
        return 'Appointments';
    }

    public function getIcon(): Heroicon
    {
        return Heroicon::CalendarDays;
    }

    public function getColor(): array
    {
        return Color::Blue;
    }

    /** @return list<CalendarEvent> */
    public function getEvents(CalendarRange $range, int $limit): array
    {
        return [];
    }
}
```

Register the configured widget through Filament as usual:

```php
AppointmentsCalendarWidget::make();
```

Tempo requests only the currently selected sources, merges and sorts their events, and applies the global `phpinnacle-tempo.calendar.event_limit`. It mounts `recordAction()` when an event has a URL or its `viewable` flag is enabled. Override `recordAction()` with `CalendarRecordAction` when clicks should open a modal or perform another operation. The clicked `CalendarEvent` is injected into a callback parameter named `$event`. Event details cross the browser; use the event ID to reload and authorize domain records before reading or changing protected data.

Event sources may expand RFC 5545 recurrence rules without coupling Tempo to their storage model:

```php
use Carbon\CarbonImmutable;
use PHPinnacle\Tempo\Calendar\CalendarRange;
use PHPinnacle\Tempo\Calendar\RecurrenceExpander;
use PHPinnacle\Tempo\Calendar\RecurringEvent;

$range = CalendarRange::create('2026-07-01', '2026-08-01')
    ?? throw new InvalidArgumentException('Invalid calendar range.');

$occurrences = app(RecurrenceExpander::class)->expand(
    new RecurringEvent(
        rule: 'FREQ=YEARLY',
        start: CarbonImmutable::parse('1990-07-15'),
    ),
    $range,
    limit: 100,
);
```

The expander preserves event duration, includes occurrences overlapping the beginning of the requested range, and returns at most the requested limit. Invalid recurrence rules retain the underlying RRULE exception semantics.

## License

The MIT License (MIT). See [License File](LICENSE.md).
