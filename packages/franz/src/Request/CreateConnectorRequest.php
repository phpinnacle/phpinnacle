<?php

namespace PHPinnacle\Franz\Request;

use PHPinnacle\Franz\Enum\InitialState;

final readonly class CreateConnectorRequest
{
    /** @param array<string, string> $config */
    public function __construct(
        public string $name,
        public array $config,
        public ?InitialState $initialState = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $body = ['name' => $this->name, 'config' => (object) $this->config];

        if ($this->initialState !== null) {
            $body['initial_state'] = $this->initialState->value;
        }

        return $body;
    }
}
