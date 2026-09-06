<?php

namespace PHPinnacle\Franz\Context;

use PHPinnacle\Franz\Request\ConnectorConfigRequest;
use PHPinnacle\Franz\Response\ConfigDefinitionResponse;
use PHPinnacle\Franz\Response\ConfigValidationResponse;
use PHPinnacle\Franz\Support\ResourcePath;
use PHPinnacle\Franz\Support\ResponseValue;
use PHPinnacle\Franz\Support\Transport;

final readonly class PluginContext
{
    private string $path;

    public function __construct(
        private Transport $transport,
        string $pluginType,
    ) {
        $this->path = '/connector-plugins/' . ResourcePath::segment($pluginType);
    }

    /** @return list<ConfigDefinitionResponse> */
    public function config(?string $version = null): array
    {
        return array_map(
            ConfigDefinitionResponse::fromArray(...),
            ResponseValue::objects($this->transport->send(
                'GET',
                $this->path . '/config',
                $version === null ? [] : ['version' => $version],
            )),
        );
    }

    public function validateConfig(ConnectorConfigRequest $request): ConfigValidationResponse
    {
        return ConfigValidationResponse::fromArray(ResponseValue::object($this->transport->send(
            'PUT',
            $this->path . '/config/validate',
            body: $request->toArray(),
        )));
    }
}
