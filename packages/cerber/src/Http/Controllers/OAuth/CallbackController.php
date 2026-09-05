<?php

namespace PHPinnacle\Cerber\Http\Controllers\OAuth;

use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPinnacle\Cerber\Exceptions\OAuthException;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\Pages\EditProfile;
use PHPinnacle\Cerber\Services\UserLinker;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

readonly class CallbackController
{
    public function __construct(
        protected UserLinker $linker,
    ) {}

    public function __invoke(Provider $provider, Request $request): RedirectResponse|View
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if ($request->session()->get('oauth_test_mode')) {
            return app(TestController::class)->callback($provider, $request);
        }

        if ($request->session()->get('oauth_link_mode')) {
            return $this->handleProfileLinking($provider, $request);
        }

        try {
            $user = $this->linker->findOrCreateUser($provider);
        } catch (OAuthException $e) {
            return redirect($panel->getLoginUrl())
                ->with('error', $e->getError());
        }

        if ($user->roles()->count() === 0) {
            return redirect($panel->getLoginUrl())
                ->with('error', __('phpinnacle-cerber::auth.login.no_permission_contact_admin'));
        }

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->intended($panel->getHomeUrl());
    }

    private function handleProfileLinking(Provider $provider, Request $request): RedirectResponse
    {
        $panel = Filament::getCurrentOrDefaultPanel();
        $profileUrl = $panel->getProfileUrl() ?? EditProfile::getUrl();

        try {
            $this->linker->linkAccount($provider, $request->user());

            return redirect($profileUrl)
                ->with('success', __('phpinnacle-cerber::auth.account_linked'));
        } catch (Throwable) {
            return redirect($profileUrl)
                ->with('error', __('phpinnacle-cerber::auth.errors.callback_failed'));
        } finally {
            $request->session()->forget(['oauth_link_mode']);
        }
    }
}
