<?php

namespace PHPinnacle\Langcat\Request\Webhooks;

use LogicException;
use PHPinnacle\Langcat\Enum\WebhookEvent;
use PHPinnacle\Langcat\Support\RequestValue;

final class CreateWebhookRequest
{
    private ?WebhookEvent $event = null;

    private ?string $url = null;

    public static function make(): self
    {
        return new self;
    }

    public function event(WebhookEvent $event): self
    {
        $this->event = $event;

        return $this;
    }

    /** @return array{event: string, url: string} */
    public function toArray(): array
    {
        return [
            'event' => ($this->event ?? throw new LogicException('Webhook event is required.'))->value,
            'url' => $this->url ?? throw new LogicException('Webhook URL is required.'),
        ];
    }

    public function url(string $url): self
    {
        $this->url = RequestValue::httpUrl($url, 'Webhook URL');

        return $this;
    }
}
