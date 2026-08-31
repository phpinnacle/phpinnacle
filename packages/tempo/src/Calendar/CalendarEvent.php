<?php

namespace PHPinnacle\Tempo\Calendar;

use Illuminate\Support\Str;
use JsonSerializable;

final readonly class CalendarEvent implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $title,
        public string $start,
        public ?string $end,
        public bool $allDay,
        public bool $viewable = false,
        public ?string $color = null,
        public ?string $url = null,
    ) {}

    /**
     * @param  array{
     *     id: string,
     *     title: string,
     *     start: string,
     *     end: string|null,
     *     allDay: bool,
     *     viewable: bool,
     *     color: string|null,
     *     url: string|null
     * }  $event
     */
    public static function fromArray(array $event): self
    {
        return new self(
            id: $event['id'],
            title: $event['title'],
            start: $event['start'],
            end: $event['end'],
            allDay: $event['allDay'],
            viewable: $event['viewable'],
            color: $event['color'],
            url: $event['url'],
        );
    }

    /**
     * @return array{
     *     id: string,
     *     title: string,
     *     start: string,
     *     end: string|null,
     *     allDay: bool,
     *     url?: string,
     *     backgroundColor?: string,
     *     borderColor?: string,
     *     extendedProps: array{viewable: bool, url: string|null}
     * }
     */
    public function jsonSerialize(): array
    {
        $url = Str::sanitizeUrl($this->url);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'start' => $this->start,
            'end' => $this->end,
            'allDay' => $this->allDay,
            ...($url !== null ? ['url' => $url] : []),
            ...(
                $this->color !== null
                    ? [
                        'backgroundColor' => $this->color,
                        'borderColor' => $this->color,
                    ] : []
            ),
            'extendedProps' => [
                'viewable' => $this->viewable,
                'url' => $url,
            ],
        ];
    }
}
