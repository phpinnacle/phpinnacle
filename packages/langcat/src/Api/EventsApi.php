<?php

namespace PHPinnacle\Langcat\Api;

use PHPinnacle\Langcat\Request\Events\ListEventsRequest;
use PHPinnacle\Langcat\Response\Events\EventResponse;
use PHPinnacle\Langcat\Response\Events\EventsResponse;
use PHPinnacle\Langcat\Support\ResourcePath;
use PHPinnacle\Langcat\Support\Transport;

final readonly class EventsApi
{
    public function __construct(
        private Transport $transport,
    ) {}

    public function all(?ListEventsRequest $request = null): EventsResponse
    {
        return EventsResponse::fromArray($this->transport->send('GET', '/events', $request?->toQuery() ?? []));
    }

    public function get(int $eventId): EventResponse
    {
        return EventResponse::fromArray($this->transport->send('GET', ResourcePath::id('events', $eventId)));
    }
}
