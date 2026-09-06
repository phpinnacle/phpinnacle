<?php

namespace PHPinnacle\Franz;

use PHPinnacle\Franz\Context\ConnectorContext;
use PHPinnacle\Franz\Context\LoggerContext;
use PHPinnacle\Franz\Context\PluginContext;
use PHPinnacle\Franz\Request\CreateConnectorRequest;
use PHPinnacle\Franz\Response\ConnectorResponse;
use PHPinnacle\Franz\Response\ExpandedConnectorResponse;
use PHPinnacle\Franz\Response\LoggerResponse;
use PHPinnacle\Franz\Response\PluginResponse;
use PHPinnacle\Franz\Response\WorkerHealthResponse;
use PHPinnacle\Franz\Response\WorkerInfoResponse;
use PHPinnacle\Franz\Support\ResponseValue;
use PHPinnacle\Franz\Support\Transport;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final readonly class Client
{
    private Transport $transport;

    /** @param array<string, string> $headers */
    public function __construct(
        string $baseUri,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        #[\SensitiveParameter]
        array $headers = [],
    ) {
        $this->transport = new Transport($baseUri, $httpClient, $requestFactory, $streamFactory, $headers);
    }

    public function info(): WorkerInfoResponse
    {
        return WorkerInfoResponse::fromArray(ResponseValue::object($this->transport->send('GET', '/')));
    }

    public function health(): WorkerHealthResponse
    {
        return WorkerHealthResponse::fromArray(ResponseValue::object($this->transport->send('GET', '/health')));
    }

    /** @return list<string> */
    public function connectors(): array
    {
        return ResponseValue::strings($this->transport->send('GET', '/connectors'));
    }

    /** @return array<string, ExpandedConnectorResponse> */
    public function expandedConnectors(): array
    {
        $payload = ResponseValue::object($this->transport->send('GET', '/connectors', ['expand' => [
            'info',
            'status',
        ]]));
        $connectors = [];

        foreach ($payload as $name => $item) {
            $connectors[(string) $name] = ExpandedConnectorResponse::fromArray(ResponseValue::object($item));
        }

        return $connectors;
    }

    public function createConnector(CreateConnectorRequest $request): ConnectorResponse
    {
        return ConnectorResponse::fromArray(ResponseValue::object($this->transport->send(
            'POST',
            '/connectors',
            body: $request->toArray(),
        )));
    }

    public function connector(string $name): ConnectorContext
    {
        return new ConnectorContext($this->transport, $name);
    }

    /** @return list<PluginResponse> */
    public function plugins(bool $connectorsOnly = true): array
    {
        return array_map(
            PluginResponse::fromArray(...),
            ResponseValue::objects($this->transport->send('GET', '/connector-plugins', [
                'connectorsOnly' => $connectorsOnly,
            ])),
        );
    }

    public function plugin(string $pluginType): PluginContext
    {
        return new PluginContext($this->transport, $pluginType);
    }

    /** @return array<string, LoggerResponse> */
    public function loggers(): array
    {
        $payload = ResponseValue::object($this->transport->send('GET', '/admin/loggers'));
        $loggers = [];

        foreach ($payload as $name => $item) {
            $loggers[(string) $name] = LoggerResponse::fromArray(ResponseValue::object($item));
        }

        return $loggers;
    }

    public function logger(string $name): LoggerContext
    {
        return new LoggerContext($this->transport, $name);
    }

    /**
     * @param array<string, string|int|bool|list<string>> $query
     * @param array<string, mixed>|null $body
     * @return array<array-key, mixed>|null
     */
    public function request(string $method, string $path, array $query = [], ?array $body = null): ?array
    {
        return $this->transport->send($method, $path, $query, $body);
    }
}
