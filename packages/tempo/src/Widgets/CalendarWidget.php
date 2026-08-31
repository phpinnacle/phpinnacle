<?php

namespace PHPinnacle\Tempo\Widgets;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Livewire\Attributes\Renderless;
use PHPinnacle\Tempo\Calendar\CalendarEvent;
use PHPinnacle\Tempo\Calendar\CalendarRange;
use PHPinnacle\Tempo\Calendar\CalendarRecordAction;
use PHPinnacle\Tempo\Contracts\EventSource;
use PHPinnacle\Tempo\Enums\CalendarView;

use function Filament\Support\get_color_css_variables;

abstract class CalendarWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected static bool $isLazy = false;

    public ?string $calendarHeading = null;

    public string $initialView = CalendarView::Month->value;

    /** @var array<int, string> */
    public array $availableViews = [
        CalendarView::Month->value,
        CalendarView::Week->value,
        CalendarView::List->value,
    ];

    public int $weekStartsOn = 1;

    public bool $showWeekends = true;

    public bool $showEventTime = true;

    public int $maxEventsPerDay = 3;

    protected string $view = 'phpinnacle-tempo::widgets.calendar';

    protected int|string|array $columnSpan = 'full';

    /**
     * @param  array<int, array<string, mixed>>  $events
     * @return array{events: array<int, array<string, mixed>>, status: string, truncated: bool}
     */
    private static function calendarResponse(
        array $events = [],
        string $status = 'ready',
        bool $truncated = false,
    ): array {
        return compact('events', 'status', 'truncated');
    }

    /** @return list<EventSource> */
    abstract public function getEventSources(): array;

    /** @return array{events: array<int, array<string, mixed>>, status: string, truncated: bool} */
    #[Renderless]
    public function fetchCalendarEvents(string $rangeStart, string $rangeEnd, ?array $eventSourceKeys = null): array
    {
        $range = CalendarRange::create($rangeStart, $rangeEnd);

        if ($range === null) {
            return self::calendarResponse(status: 'invalid_range');
        }

        $limit = max(1, Config::integer('phpinnacle-tempo.calendar.event_limit'));
        $eventSources = $this->eventSources();
        $selectedEventSources = null;

        if ($eventSourceKeys !== null) {
            $selectedEventSources = [];

            foreach ($eventSourceKeys as $eventSourceKey) {
                if (is_string($eventSourceKey)) {
                    $selectedEventSources["key:{$eventSourceKey}"] = true;
                }
            }
        }

        $events = [];

        foreach ($eventSources as $eventSourceKey => $eventSource) {
            if ($selectedEventSources !== null && !isset($selectedEventSources[$eventSourceKey])) {
                continue;
            }

            foreach ($eventSource->getEvents($range, $limit + 1) as $event) {
                $events[] = [
                    'event' => $event,
                    'source' => $eventSource->getKey(),
                ];
            }
        }

        usort($events, fn (array $left, array $right) => $left['event']->start <=> $right['event']->start);

        return self::calendarResponse(
            events: array_map(
                function (array $item) {
                    /** @var CalendarEvent $event */
                    $event = $item['event'];
                    $payload = $event->jsonSerialize();
                    $payload['extendedProps']['actionable'] =
                        $event->viewable || $payload['extendedProps']['url'] !== null;
                    $payload['extendedProps']['source'] = $item['source'];

                    return $payload;
                },
                array_slice($events, 0, $limit),
            ),
            truncated: count($events) > $limit,
        );
    }

    public function recordAction(): Action
    {
        return CalendarRecordAction::make('openEvent')
            ->action(function (CalendarEvent $event) {
                $url = Str::sanitizeUrl($event->url);

                if ($url !== null) {
                    $this->redirect($url);
                }
            });
    }

    protected function getViewData(): array
    {
        $eventSources = array_values($this->eventSources());

        return [
            'heading' => $this->calendarHeading,
            'initialView' => $this->initialView,
            'availableViews' => $this->availableViews,
            'weekStartsOn' => $this->weekStartsOn,
            'showWeekends' => $this->showWeekends,
            'showEventTime' => $this->showEventTime,
            'maxEventsPerDay' => $this->maxEventsPerDay,
            'locale' => Config::string('app.locale'),
            'eventSources' => array_map(
                function (EventSource $eventSource) {
                    $color = $eventSource->getColor();

                    return [
                        'key' => $eventSource->getKey(),
                        'label' => $eventSource->getLabel(),
                        'icon' => $eventSource->getIcon(),
                        'color' => $color,
                        'colorStyle' => get_color_css_variables(
                            $color,
                            [300, 400, 500, 600, 700],
                        ),
                    ];
                },
                $eventSources,
            ),
            'eventSourceKeys' => array_map(
                fn (EventSource $eventSource) => $eventSource->getKey(),
                $eventSources,
            ),
        ];
    }

    /** @return array<string, EventSource> */
    private function eventSources(): array
    {
        $eventSources = [];

        foreach ($this->getEventSources() as $eventSource) {
            $eventSources['key:' . $eventSource->getKey()] = $eventSource;
        }

        return $eventSources;
    }
}
