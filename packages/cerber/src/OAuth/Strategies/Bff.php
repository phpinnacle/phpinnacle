<?php

namespace PHPinnacle\Cerber\OAuth\Strategies;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Uri;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Two\AbstractProvider as OAuth2Provider;
use PHPinnacle\Cerber\Exceptions\OAuthException;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\OAuth\CallbackData;
use PHPinnacle\Cerber\OAuth\OAuth;
use PHPinnacle\Cerber\Services\ProviderRegistry;
use PHPinnacle\Cerber\Services\UserLinker;

readonly class Bff implements OAuth
{
    public function __construct(
        private ProviderRegistry $providers,
        private UserLinker $linker,
    ) {}

    public function redirect(Provider $provider): RedirectResponse
    {
        return $this->buildDriver($provider)
            ->redirect();
    }

    public function callback(Provider $provider, CallbackData $data): RedirectResponse
    {
        $socialiteUser = $this->fetchSocialiteUser($provider, $data);

        $user = $this->linker->findOrCreateUser($provider, $socialiteUser);

        if ($user->roles()->count() === 0) {
            throw new OAuthException(
                __('phpinnacle-cerber::auth.errors.callback_failed'),
                __('phpinnacle-cerber::auth.login.no_permission_contact_admin'),
            );
        }

        Auth::guard('web')->login($user);
        request()->session()->regenerate();

        $frontendUrl = config('phpinnacle-cerber.api.frontend_url');

        if (!is_string($frontendUrl) || $frontendUrl === '') {
            throw new OAuthException(
                __('phpinnacle-cerber::auth.errors.callback_failed'),
                __('phpinnacle-cerber::auth.errors.invalid_frontend_url'),
            );
        }

        return redirect()->to($frontendUrl . '/dashboard');
    }

    private function buildDriver(Provider $provider): OAuth2Provider
    {
        $callbackUrl = config('phpinnacle-cerber.api.callback_url');

        if (!is_string($callbackUrl) || $callbackUrl === '') {
            throw new OAuthException(
                __('phpinnacle-cerber::auth.errors.callback_failed'),
                __('phpinnacle-cerber::auth.errors.invalid_callback_url'),
            );
        }

        $config = $provider->config;
        $config['redirect'] = (string) Uri::of($callbackUrl)
            ->withQuery(['provider' => $provider->id]);

        $driver = $this->providers->build($provider->type, $config);

        if (!$driver instanceof OAuth2Provider) {
            throw new OAuthException(
                __('phpinnacle-cerber::auth.errors.callback_failed'),
                'BFF OAuth is only supported for OAuth2 providers.',
            );
        }

        return $driver;
    }

    private function fetchSocialiteUser(Provider $provider, CallbackData $data): SocialiteUser
    {
        $driver = $this->buildDriver($provider)
            ->setRequest($this->syntheticRequest($data));

        try {
            return $driver->user();
        } catch (\Throwable $e) {
            throw new OAuthException(
                __('phpinnacle-cerber::auth.errors.callback_failed'),
                $e,
            );
        }
    }

    private function syntheticRequest(CallbackData $data): Request
    {
        return Request::create(
            uri: (string) config('phpinnacle-cerber.api.callback_url'),
            method: 'GET',
            parameters: $data->parameters,
        );
    }
}
