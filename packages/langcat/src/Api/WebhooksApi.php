<?php

namespace PHPinnacle\Langcat\Api;

use PHPinnacle\Langcat\Request\Webhooks\CreateWebhookRequest;
use PHPinnacle\Langcat\Request\Webhooks\ListWebhooksRequest;
use PHPinnacle\Langcat\Response\Shared\EmptyResponse;
use PHPinnacle\Langcat\Response\Shared\IdResponse;
use PHPinnacle\Langcat\Response\Webhooks\WebhookResponse;
use PHPinnacle\Langcat\Response\Webhooks\WebhooksResponse;
use PHPinnacle\Langcat\Support\ResourcePath;
use PHPinnacle\Langcat\Support\Transport;
use PHPinnacle\Langcat\Support\TransportMode;

final readonly class WebhooksApi
{
    public function __construct(
        private Transport $transport,
    ) {}

    public function all(?ListWebhooksRequest $request = null): WebhooksResponse
    {
        return WebhooksResponse::fromArray($this->transport->send('GET', '/webhooks', $request?->toQuery() ?? []));
    }

    public function get(int $webhookId): WebhookResponse
    {
        return WebhookResponse::fromArray($this->transport->send('GET', ResourcePath::id('webhooks', $webhookId)));
    }

    public function create(CreateWebhookRequest $request): IdResponse
    {
        return IdResponse::fromArray($this->transport->send('POST', '/webhooks', body: $request->toArray()));
    }

    public function delete(int $webhookId): EmptyResponse
    {
        return EmptyResponse::fromArray($this->transport->send(
            'DELETE',
            ResourcePath::id('webhooks', $webhookId),
            mode: TransportMode::EmptyResponse,
        ));
    }
}
