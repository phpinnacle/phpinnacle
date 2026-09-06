<?php

namespace PHPinnacle\Franz\Request;

final readonly class ConnectorConfigRequest
{
    /** @param array<string, string> $config */
    public function __construct(
        public array $config,
    ) {}

    /** @return array<string, string> */
    public function toArray(): array
    {
        return $this->config;
    }
}
