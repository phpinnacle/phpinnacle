<?php

namespace PHPinnacle\Cerber\Http\Controllers\OAuth;

use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\Services\ProviderRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;

readonly class RedirectController
{
    public function __construct(
        private ProviderRegistry $providers,
    ) {}

    public function __invoke(Provider $provider): RedirectResponse
    {
        return $this->providers->build($provider->type, $provider->config)->redirect();
    }
}
