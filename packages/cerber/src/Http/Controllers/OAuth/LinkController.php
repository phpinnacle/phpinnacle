<?php

namespace PHPinnacle\Cerber\Http\Controllers\OAuth;

use PHPinnacle\Cerber\CerberPlugin;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\Services\SessionOAuth;
use Symfony\Component\HttpFoundation\RedirectResponse;

readonly class LinkController
{
    public function __construct(
        private SessionOAuth $oauth,
    ) {}

    public function __invoke(Provider $provider): RedirectResponse
    {
        $context = CerberPlugin::get()->getSocialAccountContext();

        return $this->oauth->redirect($context, $provider, linkUserId: auth()->guard($context->guard())->id());
    }
}
