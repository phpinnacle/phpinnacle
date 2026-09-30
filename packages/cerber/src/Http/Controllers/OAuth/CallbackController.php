<?php

namespace PHPinnacle\Cerber\Http\Controllers\OAuth;

use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use PHPinnacle\Cerber\Enums\ProviderScope;
use PHPinnacle\Cerber\Exceptions\OAuthException;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\OAuth\CallbackData;
use PHPinnacle\Cerber\OAuth\OAuthFactory;
use PHPinnacle\Cerber\Pages\EditProfile;
use PHPinnacle\Cerber\Services\UserLinker;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

readonly class CallbackController
{
    public function __construct(
        private OAuthFactory $factory,
        private UserLinker $linker,
    ) {}

    public function __invoke(Provider $provider, Request $request): RedirectResponse|View
    {
        if ($request->session()->get('oauth_test_mode')) {
            return app(TestController::class)->callback($provider, $request);
        }

        if ($request->session()->get('oauth_link_mode')) {
            return $this->handleProfileLinking($provider, $request);
        }

        try {
            return $this->factory
                ->make(ProviderScope::Session)
                ->callback($provider, CallbackData::fromRequest($request));
        } catch (OAuthException $e) {
            $panel = \Filament\Facades\Filament::getCurrentOrDefaultPanel();

            return redirect($panel->getLoginUrl())->with('error', $e->getMessage());
        }
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
