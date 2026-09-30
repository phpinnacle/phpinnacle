<?php

namespace PHPinnacle\Cerber\Http\Controllers\OAuth;

use Illuminate\Http\Request;
use PHPinnacle\Cerber\Enums\ProviderScope;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\OAuth\OAuthFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

readonly class LinkController
{
    public function __construct(
        private OAuthFactory $factory,
    ) {}

    public function __invoke(Provider $provider, Request $request): RedirectResponse
    {
        $oauthProvider = $this->factory
            ->make(ProviderScope::Session);

        $request->session()->put('oauth_link_mode', true);

        return $oauthProvider->redirect($provider);
    }
}
