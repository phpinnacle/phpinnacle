<?php

namespace PHPinnacle\Langcat\Response\Events;

use PHPinnacle\Langcat\Enum\EventContext;
use PHPinnacle\Langcat\Enum\EventType;
use PHPinnacle\Langcat\Response\Users\UserResponse;
use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class EventResponse
{
    public function __construct(
        public int $id,
        public EventType $type,
        public EventContext $context,
        public EventStudentDetailsResponse|EventGroupDetailsResponse|EventStudentInGroupDetailsResponse $details,
        public UserResponse $createdBy,
        public string $ipAddress,
        public string $date,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $context = EventContext::from(ResponseValue::string($payload, 'context'));
        $details = ResponseValue::object($payload, 'details');

        return new self(
            ResponseValue::int($payload, 'id'),
            EventType::from(ResponseValue::string($payload, 'type')),
            $context,
            match ($context) {
                EventContext::Student => EventStudentDetailsResponse::fromArray($details),
                EventContext::Group => EventGroupDetailsResponse::fromArray($details),
                EventContext::StudentInGroup => EventStudentInGroupDetailsResponse::fromArray($details),
            },
            UserResponse::fromArray(ResponseValue::object($payload, 'createdBy')),
            ResponseValue::string($payload, 'ipAddress'),
            ResponseValue::string($payload, 'date'),
        );
    }
}
