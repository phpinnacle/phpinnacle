<?php

namespace PHPinnacle\Cerber\Http\Controllers\OAuth;

use Illuminate\Http\Request;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\Services\ProviderRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;

readonly class LinkController
{
    public function __construct(
        private ProviderRegistry $providers,
    ) {}

    public function __invoke(Provider $provider, Request $request): RedirectResponse
    {
        $oauthProvider = $this->providers->build($provider->type, $provider->config);

        $request->session()->put('oauth_link_mode', true);

        return $oauthProvider->redirect();
    }
}
