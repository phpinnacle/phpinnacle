<?php

namespace PHPinnacle\Cerber\OAuth\Strategies;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Uri;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Two\AbstractProvider as OAuth2Provider;
use PHPinnacle\Cerber\Exceptions\OAuthException;
use PHPinnacle\Cerber\Http\Resources\OAuth\AuthResource;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\OAuth\CallbackData;
use PHPinnacle\Cerber\OAuth\OAuth;
use PHPinnacle\Cerber\Services\ProviderRegistry;
use PHPinnacle\Cerber\Services\UserLinker;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

readonly class TokenHandoff implements OAuth
{
    private const string STATE_CACHE_PREFIX = 'phpinnacle-cerber:oauth_state:';
    private const string PKCE_CACHE_PREFIX = 'phpinnacle-cerber:oauth_pkce:';

    private const int STATE_TTL_MINUTES = 10;

    public function __construct(
        private ProviderRegistry $providers,
        private UserLinker $linker,
    ) {}

    public function redirect(Provider $provider): RedirectResponse
    {
        $state = Str::random(55);
        $verifier = Str::random(55);

        Cache::put(
            self::STATE_CACHE_PREFIX . $state,
            $provider->getKey(),
            now()->addMinutes(self::STATE_TTL_MINUTES),
        );

        Cache::put(
            self::PKCE_CACHE_PREFIX . $state,
            $verifier,
            now()->addMinutes(self::STATE_TTL_MINUTES),
        );

        $challenge = $this->challenge($verifier);

        return $this
            ->buildDriver($provider)
            ->with([
                'state' => $state,
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ])
            ->redirect();
    }

    public function callback(Provider $provider, CallbackData $data): JsonResponse
    {
        $this->validateState($data, $provider);

        $verifier = Cache::pull(self::PKCE_CACHE_PREFIX . $data->get('state'));

        if (!is_string($verifier) || $verifier === '') {
            throw new OAuthException(
                __('phpinnacle-cerber::auth.errors.callback_failed'),
                __('phpinnacle-cerber::auth.errors.invalid_state'),
            );
        }

        $socialiteUser = $this->fetchSocialiteUser($provider, $data, $verifier);

        $user = $this->linker->findOrCreateUser($provider, $socialiteUser);

        if ($user->roles()->count() === 0) {
            throw new OAuthException(
                __('phpinnacle-cerber::auth.errors.callback_failed'),
                __('phpinnacle-cerber::auth.login.no_permission_contact_admin'),
            );
        }

        /** @var JWTGuard $guard */
        $guard = auth('api');
        $token = $guard->login($user);

        return response()->json(new AuthResource($token, $user));
    }

    private function validateState(CallbackData $data, Provider $provider): void
    {
        $state = $data->get('state');

        if (!is_string($state) || $state === '') {
            throw new OAuthException(
                __('phpinnacle-cerber::auth.errors.callback_failed'),
                __('phpinnacle-cerber::auth.errors.invalid_state'),
            );
        }

        $providerKey = Cache::pull(self::STATE_CACHE_PREFIX . $state);

        if ($providerKey !== $provider->getKey()) {
            throw new OAuthException(
                __('phpinnacle-cerber::auth.errors.callback_failed'),
                __('phpinnacle-cerber::auth.errors.invalid_state'),
            );
        }
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
                'Stateless OAuth is only supported for OAuth2 providers.',
            );
        }

        return $driver->stateless();
    }

    private function fetchSocialiteUser(
        Provider $provider,
        CallbackData $data,
        string $verifier,
    ): SocialiteUser {
        $driver = $this
            ->buildDriver($provider)
            ->with(['code_verifier' => $verifier])
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

    private function challenge(string $verifier): string
    {
        return rtrim(
            string: str_replace(
                search: ['+', '/'],
                replace: ['-', '_'],
                subject: base64_encode(hash('sha256', $verifier, true)),
            ),
            characters: '=',
        );
    }
}
