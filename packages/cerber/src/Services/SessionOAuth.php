<?php

namespace PHPinnacle\Cerber\Services;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use PHPinnacle\Cerber\Contracts\SocialAccountContext;
use PHPinnacle\Cerber\Exceptions\OAuthException;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\OAuth\OAuthAttempt;
use PHPinnacle\Cerber\OAuth\OAuthCallback;
use PHPinnacle\Cerber\OAuth\SessionAttempt;

readonly class SessionOAuth
{
    public function __construct(
        private OAuthFlow $flow,
        private UserLinker $linker,
    ) {}

    public function redirect(
        SocialAccountContext $context,
        Provider $provider,
        ?string $linkUserId = null,
        bool $test = false,
    ): RedirectResponse {
        $state = Str::random(55);
        $verifier = Str::random(55);
        $response = $this->flow->redirect(
            $context,
            $provider,
            $this->callbackUrl($provider),
            $state,
            OAuthAttempt::challenge($verifier),
        );
        request()->session()->put('cerber.oauth.' . $state, new SessionAttempt($verifier, $linkUserId, $test));

        return $response;
    }

    public function attempt(Request $request): SessionAttempt
    {
        /** @var SessionAttempt|null $attempt */
        $attempt = $request->session()->pull('cerber.oauth.' . $request->string('state'));

        if ($attempt === null) {
            throw new OAuthException('invalid_state', 'Invalid or expired OAuth attempt.');
        }

        return $attempt;
    }

    public function user(
        SocialAccountContext $context,
        Provider $provider,
        Request $request,
        SessionAttempt $attempt,
    ): SocialiteUser {
        return $this->flow->user(
            $context,
            $provider,
            $this->callbackUrl($provider),
            new OAuthCallback(
                (string) $request->string('state'),
                $attempt->verifier,
                (string) $request->string('code'),
            ),
        );
    }

    public function login(SocialAccountContext $context, Provider $provider, SocialiteUser $identity): RedirectResponse
    {
        $panel = Filament::getCurrentOrDefaultPanel();
        $user = $this->linker->findOrCreateUser($context, $provider, $identity);

        if (!$user->canAccessPanel($panel)) {
            throw new OAuthException('access_denied', 'The user cannot access this panel.');
        }

        auth()->guard($context->guard())->login($user);
        request()->session()->regenerate();

        return redirect()->intended($panel->getHomeUrl());
    }

    private function callbackUrl(Provider $provider): string
    {
        return Filament::getCurrentOrDefaultPanel()->route('auth.callback', ['provider' => $provider->id]);
    }
}
