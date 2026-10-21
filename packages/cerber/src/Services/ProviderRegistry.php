<?php

namespace PHPinnacle\Cerber\Services;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Laravel\Socialite\Contracts\Provider;
use PHPinnacle\Cerber\AuthProvider;

#[Singleton]
class ProviderRegistry
{
    /**
     * @var array<string, AuthProvider>
     */
    private array $providers = [];

    private bool $booted = false;

    public function add(AuthProvider $provider): void
    {
        $this->providers[$provider->getClass()] = $provider;
    }

    public function get(string $class): ?AuthProvider
    {
        $this->boot();

        return $this->providers[$class] ?? null;
    }

    /**
     * @return Collection<string, AuthProvider>
     */
    public function all(): Collection
    {
        $this->boot();

        return collect($this->providers);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function build(string $class, array $config): Provider
    {
        $provider = $this->get($class);

        if ($provider === null) {
            throw new \RuntimeException("OAuth provider [{$class}] is not registered.");
        }

        return $provider->driver($config);
    }

    private function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        foreach (config('phpinnacle-cerber.auth_providers', []) as $provider) {
            if (is_string($provider)) {
                $provider = AuthProvider::make($provider);
            }

            $this->add($provider);
        }
    }
}
