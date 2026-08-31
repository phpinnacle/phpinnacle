<?php

use Filament\Actions\Action;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\SQLiteConnection;
use PHPinnacle\Tempo\Calendar\CalendarEvent;
use PHPinnacle\Tempo\Calendar\CalendarRange;
use PHPinnacle\Tempo\Calendar\CalendarRecordAction;
use PHPinnacle\Tempo\Contracts\EventSource;
use PHPinnacle\Tempo\Enums\CalendarView;
use PHPinnacle\Tempo\Filters\DateRangeFilter;
use PHPinnacle\Tempo\Filters\DateTimeRangeFilter;
use PHPinnacle\Tempo\Filters\TimeRangeFilter;
use PHPinnacle\Tempo\Forms\DatePicker;
use PHPinnacle\Tempo\Forms\DateRangePicker;
use PHPinnacle\Tempo\Forms\DateTimePicker;
use PHPinnacle\Tempo\Forms\TimePicker;
use PHPinnacle\Tempo\Widgets\CalendarWidget;
use Tests\TestCase;

use function Livewire\store;

final class TempoDefaultCalendarWidget extends CalendarWidget
{
    public function getEventSources(): array
    {
        return [];
    }
}

final readonly class TempoTestEventSource implements EventSource
{
    /** @param list<CalendarEvent> $events */
    public function __construct(
        private string $key,
        private string $label,
        private array $events,
    ) {}

    public function getColor(): array
    {
        return Color::Violet;
    }

    public function getEvents(CalendarRange $range, int $limit): array
    {
        return array_slice($this->events, 0, $limit);
    }

    public function getIcon(): Heroicon
    {
        return Heroicon::CalendarDays;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}

final class TempoConfiguredCalendarWidget extends CalendarWidget
{
    private ?CalendarEvent $handledEvent = null;

    /** @return array<string, mixed> */
    public function calendarViewData(): array
    {
        return $this->getViewData();
    }

    public function getEventSources(): array
    {
        return [
            new TempoTestEventSource(
                key: 'later',
                label: 'Later events',
                events: [
                    new CalendarEvent(
                        id: 'later',
                        title: 'Later',
                        start: '2026-07-20',
                        end: null,
                        allDay: true,
                        url: 'https://example.com/events/later',
                    ),
                ],
            ),
            new TempoTestEventSource(
                key: 'configured',
                label: 'Configured events',
                events: [
                    new CalendarEvent(
                        id: 'configured',
                        title: 'Configured',
                        start: '2026-07-10',
                        end: null,
                        allDay: true,
                        viewable: true,
                        color: '#a855f7',
                    ),
                ],
            ),
        ];
    }

    public function handledEvent(): ?CalendarEvent
    {
        return $this->handledEvent;
    }

    public function recordAction(): Action
    {
        return CalendarRecordAction::make('viewEvent')
            ->action(function (CalendarEvent $event) {
                $this->handledEvent = $event;
            });
    }
}

uses(TestCase::class);

it('provides date and time picker modes', function () {
    expect(DatePicker::make('date')->getMode())
        ->toBe('date')
        ->and(DateTimePicker::make('datetime')->getMode())
        ->toBe('datetime')
        ->and(DateRangePicker::make('range')->getMode())
        ->toBe('range')
        ->and(TimePicker::make('time')->getMode())
        ->toBe('time');
});

it('converts picker formats and provides date filters', function () {
    $picker = DatePicker::make('date')
        ->dateFormat('d.m.Y')
        ->timeFormat('H:i');

    expect($picker->getMomentDateFormat())
        ->toBe('DD.MM.YYYY')
        ->and($picker->getMomentTimeFormat())
        ->toBe('HH:mm')
        ->and(DateRangeFilter::createdAt()->getName())
        ->toBe('created_at')
        ->and(DateRangeFilter::updatedAt()->getName())
        ->toBe('updated_at');
});

it('applies temporal table filters to configured columns', function () {
    $connection = new SQLiteConnection(new PDO('sqlite::memory:'));
    $connection->statement(
        'create table events (id integer primary key, published_on date, published_at datetime, starts_at time)',
    );
    $connection->statement(<<<'SQL'
        insert into events (published_on, published_at, starts_at) values
        ('2026-01-31', '2026-01-31 10:00:00', '10:30:00'),
        ('2026-02-01', '2026-02-01 10:00:00', '10:30:00'),
        ('2026-01-15', '2026-01-15 08:00:00', '08:30:00')
        SQL);

    $eventsQuery = function () use ($connection) {
        $model = new class extends Model {
            protected $table = 'events';
        };

        return new Builder($connection->table('events'))->setModel($model);
    };
    $query = $eventsQuery();

    DateRangeFilter::make('published_period')
        ->column('published_on')
        ->apply($query, ['range' => '2026-01-01 - 2026-01-31']);
    DateTimeRangeFilter::make('published_window')
        ->column('published_at')
        ->apply($query, ['from' => '2026-01-31 09:00:00', 'to' => '2026-01-31 11:00:00']);
    TimeRangeFilter::make('starts_window')
        ->column('starts_at')
        ->apply($query, ['from' => '10:00:00', 'to' => '11:00:00']);

    expect($query->pluck('id')->all())
        ->toBe([1])
        ->and(DateRangeFilter::make('period')->column('published_on')->getColumn())
        ->toBe('published_on')
        ->and(DateRangeFilter::make('published_on')->getColumn())
        ->toBe('published_on');
});

it('opens after asynchronous initialization when the input already has focus', function () {
    $source = file_get_contents(__DIR__ . '/../../resources/js/phpicker.js');

    expect($source)
        ->toContain('this.openPickerForFocusedInput(input)')
        ->toContain('document.activeElement !== input')
        ->toContain('this.picker.show()');
});

it('normalizes supported calendar views', function () {
    expect(CalendarView::defaults())
        ->toBe(['dayGridMonth', 'timeGridWeek', 'listWeek'])
        ->and(CalendarView::values(['timeGridDay', 'unsupported', 'timeGridDay', null]))
        ->toBe(['timeGridDay']);
});

it('loads and sorts calendar events from its sources', function () {
    config()->set('phpinnacle-tempo.calendar.event_limit', 10);

    $widget = new TempoConfiguredCalendarWidget;
    $response = $widget->fetchCalendarEvents('2026-07-01', '2026-08-01');

    expect(array_column($response['events'], 'title'))
        ->toBe(['Configured', 'Later'])
        ->and($response['events'][0]['backgroundColor'])
        ->toBe('#a855f7')
        ->and($response['events'][0]['extendedProps']['source'])
        ->toBe('configured')
        ->and($response['events'][0]['extendedProps']['actionable'])
        ->toBeTrue()
        ->and($response['events'][1]['url'])
        ->toBe('https://example.com/events/later')
        ->and($response['events'][1]['extendedProps']['source'])
        ->toBe('later')
        ->and($response['events'][1]['extendedProps']['actionable'])
        ->toBeTrue()
        ->and(array_column($widget->calendarViewData()['eventSources'], 'label'))
        ->toBe([
            'Later events',
            'Configured events',
        ])
        ->and(array_column($widget->calendarViewData()['eventSources'], 'icon'))
        ->toBe([
            Heroicon::CalendarDays,
            Heroicon::CalendarDays,
        ])
        ->and(array_column($widget->calendarViewData()['eventSources'], 'color'))
        ->toBe([
            Color::Violet,
            Color::Violet,
        ])
        ->and($widget->calendarViewData()['eventSourceKeys'])
        ->toBe(['later', 'configured'])
        ->and($response['truncated'])
        ->toBeFalse()
        ->and($widget->fetchCalendarEvents('invalid', '2026-08-01')['status'])
        ->toBe('invalid_range');
});

it('loads only selected event sources', function () {
    config()->set('phpinnacle-tempo.calendar.event_limit', 10);

    $widget = new TempoConfiguredCalendarWidget;

    expect($widget->fetchCalendarEvents('2026-07-01', '2026-08-01', ['configured'])['events'])
        ->toHaveCount(1)
        ->and($widget->fetchCalendarEvents('2026-07-01', '2026-08-01', [])['events'])
        ->toBe([]);
});

it('passes the clicked event to the record action as its event', function () {
    $widget = new TempoConfiguredCalendarWidget;

    $widget->mountAction('record', [
        'event' => [
            'id' => 'configured',
            'title' => 'Configured',
            'start' => '2026-07-10',
            'end' => null,
            'allDay' => true,
            'viewable' => true,
            'color' => '#a855f7',
            'url' => null,
        ],
    ]);

    expect($widget->handledEvent())
        ->toEqual(new CalendarEvent(
            id: 'configured',
            title: 'Configured',
            start: '2026-07-10',
            end: null,
            allDay: true,
            viewable: true,
            color: '#a855f7',
            url: null,
        ));
});

it('teleports calendar action modals outside the widget container', function () {
    $action = new class('viewEvent') extends CalendarRecordAction {
        public function renderModal(): View
        {
            return new class implements View {
                public function getData(): array
                {
                    return [];
                }

                public function name(): string
                {
                    return 'modal';
                }

                public function render(): string
                {
                    return '<div class="fi-modal"></div>';
                }

                public function with($key, $value = null): static
                {
                    return $this;
                }
            };
        }
    };

    expect($action->toModalHtmlable()->toHtml())
        ->toContain('<template x-teleport="body"')
        ->toContain('wire:partial="action-modals.')
        ->toContain('<div class="fi-modal"></div>');
});

it('opens the event URL through the default record action', function () {
    $widget = new TempoDefaultCalendarWidget;

    $widget->mountAction('record', [
        'event' => [
            'id' => 'release',
            'title' => 'Release',
            'start' => '2026-07-10',
            'end' => null,
            'allDay' => true,
            'viewable' => false,
            'color' => null,
            'url' => 'https://example.com/releases/1',
        ],
    ]);

    expect(store($widget)->get('redirect'))->toBe('https://example.com/releases/1');
});

it('does not redirect to an unsafe event URL', function () {
    $widget = new TempoDefaultCalendarWidget;

    $widget->mountAction('record', [
        'event' => [
            'id' => 'release',
            'title' => 'Release',
            'start' => '2026-07-10',
            'end' => null,
            'allDay' => true,
            'viewable' => false,
            'color' => null,
            'url' => 'javascript:alert(1)',
        ],
    ]);

    expect(store($widget)->has('redirect'))->toBeFalse();
});
