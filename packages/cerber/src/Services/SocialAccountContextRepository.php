<?php

namespace PHPinnacle\Cerber\Services;

use Illuminate\Container\Attributes\Singleton;
use PHPinnacle\Cerber\Contracts\SocialAccountContext;

#[Singleton]
class SocialAccountContextRepository
{
    /** @var array<string, SocialAccountContext> */
    private array $contexts = [];

    public function add(SocialAccountContext $context): void
    {
        $this->contexts[$context->name()] = $context;
    }

    public function get(string $name): SocialAccountContext
    {
        return $this->contexts[$name];
    }

    /** @return array<string, SocialAccountContext> */
    public function all(): array
    {
        return $this->contexts;
    }
}
