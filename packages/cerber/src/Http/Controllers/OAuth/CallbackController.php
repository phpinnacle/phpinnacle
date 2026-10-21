<?php

namespace PHPinnacle\Cerber\Http\Controllers\OAuth;

use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use PHPinnacle\Cerber\CerberPlugin;
use PHPinnacle\Cerber\Contracts\SocialAccountContext;
use PHPinnacle\Cerber\Exceptions\OAuthException;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\Models\SocialAccount;
use PHPinnacle\Cerber\Models\User;
use PHPinnacle\Cerber\Pages\EditProfile;
use PHPinnacle\Cerber\Services\SessionOAuth;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

readonly class CallbackController
{
    public function __construct(
        private SessionOAuth $oauth,
    ) {}

    public function __invoke(Provider $provider, Request $request): RedirectResponse|View
    {
        $request->validate(['state' => ['required', 'string', 'size:55'], 'code' => ['nullable', 'string']]);
        $panel = Filament::getCurrentOrDefaultPanel();
        $context = CerberPlugin::get()->getSocialAccountContext();
        $attempt = null;

        try {
            $attempt = $this->oauth->attempt($request);

            if (!$request->filled('code')) {
                throw new OAuthException('callback_failed', 'OAuth authorization was not completed.');
            }

            $identity = $this->oauth->user($context, $provider, $request, $attempt);

            if ($attempt->test) {
                return $this->testResult($provider, user: $identity);
            }

            if ($attempt->linkUserId !== null) {
                return $this->link($context, $provider, $identity, $attempt->linkUserId);
            }

            return $this->oauth->login($context, $provider, $identity);
        } catch (OAuthException $e) {
            if ($attempt?->test) {
                return $this->testResult($provider, error: $e->getError());
            }

            return redirect($panel->getLoginUrl())->with('error', $e->getError());
        }
    }

    private function link(
        SocialAccountContext $context,
        Provider $provider,
        SocialiteUser $identity,
        string $userId,
    ): RedirectResponse {
        /** @var User|null $user */
        $user = auth()->guard($context->guard())->user();
        abort_unless($user !== null && $user->id === $userId, 403);
        $profileUrl = Filament::getCurrentOrDefaultPanel()->getProfileUrl() ?? EditProfile::getUrl();

        try {
            SocialAccount::link($provider, $user, $identity);
        } catch (Throwable $e) {
            report($e);

            return redirect($profileUrl)->with('error', __('phpinnacle-cerber::auth.errors.callback_failed'));
        }

        return redirect($profileUrl)->with('success', __('phpinnacle-cerber::auth.account.linked'));
    }

    private function testResult(Provider $provider, ?string $error = null, ?SocialiteUser $user = null): View
    {
        $success = $error === null;

        return view('phpinnacle-cerber::oauth.test-result', [
            'provider' => $provider->getLabel(),
            'success' => $success,
            'message' => $success
                ? __('phpinnacle-cerber::auth.test.success')
                : __('phpinnacle-cerber::auth.test.failure'),
            'error' => $error,
            'userData' => $user !== null
                ? [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                    'nickname' => $user->getNickname(),
                    'avatar' => $user->getAvatar(),
                ] : null,
        ]);
    }
}
