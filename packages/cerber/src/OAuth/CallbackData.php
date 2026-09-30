<?php

namespace PHPinnacle\Cerber\OAuth;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;

/**
 * @implements Arrayable<string, mixed>
 */
readonly class CallbackData implements Arrayable
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        public array $parameters,
    ) {}

    /**
     * @param array<string, mixed> $parameters
     */
    public static function fromArray(array $parameters): self
    {
        return new self($parameters);
    }

    public static function fromRequest(Request $request): self
    {
        return new self($request->all());
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->parameters;
    }
}
