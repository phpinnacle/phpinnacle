<?php

namespace PHPinnacle\Franz\Context;

use PHPinnacle\Franz\Request\AlterConnectorOffsetsRequest;
use PHPinnacle\Franz\Request\ConnectorConfigRequest;
use PHPinnacle\Franz\Request\PatchConnectorConfigRequest;
use PHPinnacle\Franz\Response\ConnectorResponse;
use PHPinnacle\Franz\Response\ConnectorStatusResponse;
use PHPinnacle\Franz\Response\MessageResponse;
use PHPinnacle\Franz\Response\TaskResponse;
use PHPinnacle\Franz\Support\ResourcePath;
use PHPinnacle\Franz\Support\ResponseValue;
use PHPinnacle\Franz\Support\Transport;
use PHPinnacle\Franz\Value\ConnectorOffset;

final readonly class ConnectorContext
{
    private string $path;

    public function __construct(
        private Transport $transport,
        private string $name,
    ) {
        $this->path = '/connectors/' . ResourcePath::segment($name);
    }

    public function details(): ConnectorResponse
    {
        return ConnectorResponse::fromArray(ResponseValue::object($this->transport->send('GET', $this->path)));
    }

    /** @return array<string, string> */
    public function config(): array
    {
        return ResponseValue::config($this->transport->send('GET', $this->path . '/config'));
    }

    /** Creates the connector if absent; otherwise replaces its complete configuration. */
    public function updateConfig(ConnectorConfigRequest $request): ConnectorResponse
    {
        return ConnectorResponse::fromArray(ResponseValue::object($this->transport->send(
            'PUT',
            $this->path . '/config',
            body: $request->toArray(),
        )));
    }

    public function patchConfig(PatchConnectorConfigRequest $request): ConnectorResponse
    {
        return ConnectorResponse::fromArray(ResponseValue::object($this->transport->send(
            'PATCH',
            $this->path . '/config',
            body: $request->toArray(),
        )));
    }

    public function delete(): void
    {
        $this->transport->send('DELETE', $this->path);
    }

    public function status(): ConnectorStatusResponse
    {
        return ConnectorStatusResponse::fromArray(ResponseValue::object($this->transport->send(
            'GET',
            $this->path . '/status',
        )));
    }

    public function pause(): void
    {
        $this->transport->send('PUT', $this->path . '/pause');
    }

    public function resume(): void
    {
        $this->transport->send('PUT', $this->path . '/resume');
    }

    public function stop(): void
    {
        $this->transport->send('PUT', $this->path . '/stop');
    }

    /** Returns null when Kafka Connect acknowledges the restart without a response body. */
    public function restart(bool $includeTasks = false, bool $onlyFailed = false): ?ConnectorStatusResponse
    {
        $payload = $this->transport->send('POST', $this->path . '/restart', [
            'includeTasks' => $includeTasks,
            'onlyFailed' => $onlyFailed,
        ]);

        return $payload === null ? null : ConnectorStatusResponse::fromArray($payload);
    }

    /** @return list<TaskResponse> */
    public function tasks(): array
    {
        return array_map(
            TaskResponse::fromArray(...),
            ResponseValue::objects($this->transport->send('GET', $this->path . '/tasks')),
        );
    }

    public function task(int $taskId): TaskContext
    {
        return new TaskContext($this->transport, $this->name, $taskId);
    }

    /** @return list<string> */
    public function topics(): array
    {
        $payload = ResponseValue::object($this->transport->send('GET', $this->path . '/topics'));
        $topics = ResponseValue::object($payload[$this->name] ?? null);

        return ResponseValue::strings($topics['topics'] ?? null);
    }

    /** Clears topic tracking; does not delete Kafka topics or reset offsets. */
    public function resetTopics(): void
    {
        $this->transport->send('PUT', $this->path . '/topics/reset');
    }

    /** @return list<ConnectorOffset> */
    public function offsets(): array
    {
        $payload = ResponseValue::object($this->transport->send('GET', $this->path . '/offsets'));

        return array_map(ConnectorOffset::fromArray(...), ResponseValue::objects($payload['offsets'] ?? null));
    }

    public function alterOffsets(AlterConnectorOffsetsRequest $request): MessageResponse
    {
        return MessageResponse::fromArray(ResponseValue::object($this->transport->send(
            'PATCH',
            $this->path . '/offsets',
            body: $request->toArray(),
        )));
    }

    public function resetOffsets(): MessageResponse
    {
        return MessageResponse::fromArray(ResponseValue::object($this->transport->send(
            'DELETE',
            $this->path . '/offsets',
        )));
    }
}
