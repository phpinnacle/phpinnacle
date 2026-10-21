<?php

namespace PHPinnacle\Cerber\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Two\AbstractProvider;
use PHPinnacle\Cerber\Contracts\SocialAccountContext;
use PHPinnacle\Cerber\Exceptions\OAuthException;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\OAuth\OAuthAttempt;
use PHPinnacle\Cerber\OAuth\OAuthCallback;
use Throwable;

readonly class OAuthFlow
{
    private const string CACHE_PREFIX = 'phpinnacle-cerber:oauth:';

    public function __construct(
        private ProviderRegistry $providers,
    ) {}

    public function redirect(
        SocialAccountContext $context,
        Provider $provider,
        string $callbackUrl,
        string $state,
        string $challenge,
    ): RedirectResponse {
        abort_unless($provider->isAvailableTo($context), 404);

        $driver = $this->driver($provider, $callbackUrl);
        Cache::put(
            self::CACHE_PREFIX . $state,
            new OAuthAttempt($context->name(), $provider->id, $callbackUrl, $challenge),
            now()->addMinutes(10),
        );

        return $driver->with([
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ])->redirect();
    }

    public function user(
        SocialAccountContext $context,
        Provider $provider,
        string $callbackUrl,
        OAuthCallback $callback,
    ): SocialiteUser {
        abort_unless($provider->isAvailableTo($context), 404);

        $key = self::CACHE_PREFIX . $callback->state;
        $consumed = Cache::lock($key . ':lock', 10)->get(function () use (
            $key,
            $context,
            $provider,
            $callbackUrl,
            $callback,
        ) {
            /** @var OAuthAttempt|null $attempt */
            $attempt = Cache::get($key);

            if (
                $attempt === null
                || !$attempt->matches($context->name(), $provider->id, $callbackUrl, $callback->verifier)
            ) {
                return false;
            }

            Cache::forget($key);

            return true;
        });

        if (!$consumed) {
            throw new OAuthException('invalid_state', 'Invalid or expired OAuth attempt.');
        }

        $driver = $this
            ->driver($provider, $callbackUrl)
            ->with(['code_verifier' => $callback->verifier])
            ->setRequest(Request::create($callbackUrl, 'GET', ['code' => $callback->code]));

        try {
            return $driver->user();
        } catch (Throwable $e) {
            throw new OAuthException('callback_failed', 'OAuth provider callback failed.', $e);
        }
    }

    private function driver(Provider $provider, string $callbackUrl): AbstractProvider
    {
        $config = $provider->config;
        $config['redirect'] = $callbackUrl;

        /** @var AbstractProvider $driver */
        $driver = $this->providers->build($provider->type, $config);

        return $driver->stateless();
    }
}
