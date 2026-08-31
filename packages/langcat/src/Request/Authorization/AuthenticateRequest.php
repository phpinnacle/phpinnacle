<?php

namespace PHPinnacle\Langcat\Request\Authorization;

use InvalidArgumentException;
use LogicException;

final class AuthenticateRequest
{
    private ?string $clientId = null;

    private ?string $clientSecret = null;

    public static function make(): self
    {
        return new self;
    }

    public function clientId(string $clientId): self
    {
        $this->clientId = $this->nonEmpty($clientId, 'Client ID');

        return $this;
    }

    public function clientSecret(string $clientSecret): self
    {
        $this->clientSecret = $this->nonEmpty($clientSecret, 'Client secret');

        return $this;
    }

    /** @return array{clientId: string, clientSecret: string} */
    public function toArray(): array
    {
        return [
            'clientId' => $this->clientId ?? throw new LogicException('Client ID is required.'),
            'clientSecret' => $this->clientSecret ?? throw new LogicException('Client secret is required.'),
        ];
    }

    private function nonEmpty(string $value, string $name): string
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException("{$name} cannot be empty.");
        }

        return $value;
    }
}
