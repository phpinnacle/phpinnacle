<?php

namespace PHPinnacle\Cerber\Http\Controllers\OAuth;

use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Socialite\Contracts\User;
use PHPinnacle\Cerber\Enums\ProviderScope;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\OAuth\OAuthFactory;
use PHPinnacle\Cerber\Services\ProviderRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

readonly class TestController
{
    public function __construct(
        private ProviderRegistry $providers,
        private OAuthFactory $factory,
    ) {}

    public function callback(Provider $provider, Request $request): View|RedirectResponse
    {
        try {
            if (!$request->session()->get('oauth_test_mode')) {
                return redirect()->route('filament.master.auth.login');
            }

            $panel = Filament::getCurrentOrDefaultPanel();

            $config = $provider->config;
            $config['redirect'] = $panel->route('auth.callback', [
                'provider' => $provider->getKey(),
            ]);

            $user = $this->providers->build($provider->type, $config)->user();

            return $this->renderResult($provider, user: $user);
        } catch (Throwable $e) {
            return $this->renderResult($provider, error: $e->getMessage());
        } finally {
            $request->session()->forget('oauth_test_mode');
        }
    }

    public function __invoke(Provider $provider, Request $request): View|RedirectResponse
    {
        $oauthProvider = $this->factory
            ->make(ProviderScope::Session);

        $request->session()->put('oauth_test_mode', true);

        return $oauthProvider->redirect($provider);
    }

    private function renderResult(
        Provider $provider,
        ?string $error = null,
        ?User $user = null,
    ): View {
        $success = $error === null;
        $message = $success
            ? __('phpinnacle-cerber::auth.test.success')
            : __('phpinnacle-cerber::auth.test.failure');

        return view('phpinnacle-cerber::oauth.test-result', [
            'provider' => $provider->getLabel(),
            'success' => $success,
            'message' => $message,
            'error' => $error,
            'userData' => $user !== null
                ? [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                ] : null,
        ]);
    }
}
