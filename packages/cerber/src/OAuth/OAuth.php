<?php

namespace PHPinnacle\Cerber\OAuth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use PHPinnacle\Cerber\Exceptions\OAuthException;
use PHPinnacle\Cerber\Models\Provider;
use Symfony\Component\HttpFoundation\RedirectResponse as SRedirectResponse;

interface OAuth
{
    /**
     * Начать OAuth-флоу: вернуть редирект на провайдера.
     *
     * @throws OAuthException
     */
    public function redirect(Provider $provider): SRedirectResponse|RedirectResponse;

    /**
     * Завершить OAuth-флоу.
     *
     * @throws OAuthException
     */
    public function callback(Provider $provider, CallbackData $data): SRedirectResponse|RedirectResponse|JsonResponse;
}
