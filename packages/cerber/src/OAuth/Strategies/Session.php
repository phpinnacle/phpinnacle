<?php

namespace PHPinnacle\Cerber\OAuth\Strategies;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use PHPinnacle\Cerber\Exceptions\OAuthException;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\OAuth\CallbackData;
use PHPinnacle\Cerber\OAuth\OAuth;
use PHPinnacle\Cerber\Services\ProviderRegistry;
use PHPinnacle\Cerber\Services\UserLinker;
use Symfony\Component\HttpFoundation\RedirectResponse;

readonly class Session implements OAuth
{
    public function __construct(
        private ProviderRegistry $providers,
        private UserLinker $linker,
    ) {}

    public function redirect(Provider $provider): RedirectResponse
    {
        return $this->buildDriver($provider)->redirect();
    }

    public function callback(Provider $provider, CallbackData $data): RedirectResponse
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        $socialiteUser = $this->fetchSocialiteUser($provider);

        $user = $this->linker->findOrCreateUser($provider, $socialiteUser);

        if ($user->roles()->count() === 0) {
            return redirect($panel->getLoginUrl())
                ->with('error', __('phpinnacle-cerber::auth.login.no_permission_contact_admin'));
        }

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->intended($panel->getHomeUrl());
    }

    private function buildDriver(Provider $provider): SocialiteProvider
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        $config = $provider->config;
        $config['redirect'] = $panel->route('auth.callback', [
            'provider' => $provider->getKey(),
        ]);

        return $this->providers->build($provider->type, $config);
    }

    private function fetchSocialiteUser(Provider $provider): SocialiteUser
    {
        $driver = $this->buildDriver($provider);

        try {
            return $driver->user();
        } catch (\Throwable $e) {
            throw new OAuthException(
                __('phpinnacle-cerber::auth.errors.callback_failed'),
                $e,
            );
        }
    }
}
