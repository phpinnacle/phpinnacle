<?php

namespace PHPinnacle\Cerber\Services;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Laravel\Socialite\Contracts\Provider;
use PHPinnacle\Cerber\AuthProvider;

#[Singleton]
class ProviderRegistry
{
    private array $providers = [];

    public function add(AuthProvider $provider): void
    {
        $this->providers[$provider->getClass()] = $provider;
    }

    public function all(): Collection
    {
        return collect($this->providers);
    }

    public function build(string $class, array $config): Provider
    {
        return $this->get($class)->driver($config);
    }

    public function get(string $class): ?AuthProvider
    {
        return $this->providers[$class] ?? null;
    }
}
