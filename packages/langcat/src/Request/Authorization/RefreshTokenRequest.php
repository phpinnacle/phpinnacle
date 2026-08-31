<?php

namespace PHPinnacle\Langcat\Request\Authorization;

use InvalidArgumentException;
use LogicException;

final class RefreshTokenRequest
{
    private ?string $refreshToken = null;

    public static function make(): self
    {
        return new self;
    }

    public function refreshToken(string $refreshToken): self
    {
        if (trim($refreshToken) === '') {
            throw new InvalidArgumentException('Refresh token cannot be empty.');
        }

        $this->refreshToken = $refreshToken;

        return $this;
    }

    /** @return array{refreshToken: string} */
    public function toArray(): array
    {
        return [
            'refreshToken' => $this->refreshToken ?? throw new LogicException('Refresh token is required.'),
        ];
    }
}
