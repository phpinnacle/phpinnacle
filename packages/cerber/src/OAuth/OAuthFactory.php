<?php

namespace PHPinnacle\Cerber\OAuth;

use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Container\Container;
use PHPinnacle\Cerber\Enums\ProviderScope;

readonly class OAuthFactory
{
    /**
     * @param array<string, class-string<OAuth>> $strategies
     */
    public function __construct(
        private Container $container,
        #[Config('phpinnacle-cerber.strategies', [])]
        private array $strategies,
    ) {}

    public function make(ProviderScope $scope): OAuth
    {
        $class = $this->strategies[$scope->value] ?? null;

        if ($class === null) {
            throw new \RuntimeException(
                "No OAuth strategy registered for scope [{$scope->value}].",
            );
        }

        return $this->container->make($class);
    }
}
