<?php

namespace PHPinnacle\Langcat\Response\Authorization;

use PHPinnacle\Langcat\Support\ResponseValue;

final readonly class MeResponse
{
    /**
     * @param  list<string>  $roles
     * @param  list<int>  $schoolIds
     * @param  list<string>  $credentials
     */
    public function __construct(
        public int $id,
        public string $clientId,
        public array $roles,
        public array $schoolIds,
        public array $credentials,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            ResponseValue::int($payload, 'id'),
            ResponseValue::string($payload, 'clientId'),
            ResponseValue::strings($payload, 'roles'),
            ResponseValue::integers($payload, 'schoolIds'),
            ResponseValue::strings($payload, 'credentials'),
        );
    }
}
