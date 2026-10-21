<?php

namespace PHPinnacle\Cerber\Http\Controllers\OAuth;

use PHPinnacle\Cerber\CerberPlugin;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\Services\SessionOAuth;
use Symfony\Component\HttpFoundation\RedirectResponse;

readonly class RedirectController
{
    public function __construct(
        private SessionOAuth $oauth,
    ) {}

    public function __invoke(Provider $provider): RedirectResponse
    {
        return $this->oauth->redirect(CerberPlugin::get()->getSocialAccountContext(), $provider);
    }
}
