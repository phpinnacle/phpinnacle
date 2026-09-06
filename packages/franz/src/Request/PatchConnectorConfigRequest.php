<?php

namespace PHPinnacle\Franz\Request;

final readonly class PatchConnectorConfigRequest
{
    /** @param array<string, string|null> $config Null removes a configuration key. */
    public function __construct(
        public array $config,
    ) {}

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return $this->config;
    }
}
