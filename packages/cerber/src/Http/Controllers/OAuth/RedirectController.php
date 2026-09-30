<?php

namespace PHPinnacle\Cerber\Http\Controllers\OAuth;

use PHPinnacle\Cerber\Enums\ProviderScope;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\OAuth\OAuthFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

readonly class RedirectController
{
    public function __construct(
        private OAuthFactory $factory,
    ) {}

    public function __invoke(Provider $provider): RedirectResponse
    {
        return $this->factory
            ->make(ProviderScope::Session)
            ->redirect($provider);
    }
}
