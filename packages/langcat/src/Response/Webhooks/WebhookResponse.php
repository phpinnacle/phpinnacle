<?php

namespace PHPinnacle\Langcat\Response\Webhooks;

use PHPinnacle\Langcat\Enum\WebhookEvent;
use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class WebhookResponse
{
    public function __construct(
        public int $id,
        public WebhookEvent $event,
        public string $url,
        public string $createdAt,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            WebhookEvent::from(ResponseValue::string($payload, 'event')),
            ResponseValue::string($payload, 'url'),
            ResponseValue::string($payload, 'createdAt'),
        );
    }
}
