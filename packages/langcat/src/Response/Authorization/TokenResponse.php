<?php

namespace PHPinnacle\Langcat\Response\Authorization;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class TokenResponse
{
    public function __construct(
        #[\SensitiveParameter]
        public string $accessToken,
        #[\SensitiveParameter]
        public string $refreshToken,
        public string $tokenType,
        public int $expiresIn,
        public string $scope,
    ) {}

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::string($payload, 'accessToken'),
            ResponseValue::string($payload, 'refreshToken'),
            ResponseValue::string($payload, 'tokenType'),
            ResponseValue::int($payload, 'expiresIn'),
            ResponseValue::string($payload, 'scope'),
        );
    }
}
