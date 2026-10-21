<?php

namespace PHPinnacle\Cerber\Http\Controllers\OAuth\API;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use PHPinnacle\Cerber\Contracts\SocialAccountContext;
use PHPinnacle\Cerber\Exceptions\OAuthException;
use PHPinnacle\Cerber\Http\Resources\OAuth\AuthResource;
use PHPinnacle\Cerber\Http\Resources\OAuth\OAuthProviderResource;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\Models\SocialAccount;
use PHPinnacle\Cerber\Models\User;
use PHPinnacle\Cerber\OAuth\OAuthCallback;
use PHPinnacle\Cerber\Services\OAuthFlow;
use PHPinnacle\Cerber\Services\UserLinker;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class OAuthController extends Controller
{
    public function __construct(
        private readonly OAuthFlow $oauth,
        private readonly UserLinker $linker,
    ) {}

    public function providers(SocialAccountContext $socialAccountContext): JsonResponse
    {
        $this->guard($socialAccountContext);

        return response()->json(OAuthProviderResource::collection(Provider::valid($socialAccountContext)));
    }

    public function redirect(
        SocialAccountContext $socialAccountContext,
        Provider $provider,
        Request $request,
    ): JsonResponse {
        $this->guard($socialAccountContext);
        $data = $request->validate(['code_challenge' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{43}$/D']]);
        $response = $this->oauth->redirect(
            $socialAccountContext,
            $provider,
            $provider->frontendCallbackUrl(),
            Str::random(55),
            $data['code_challenge'],
        );

        return response()->json(['redirect_url' => $response->getTargetUrl()]);
    }

    public function callback(
        SocialAccountContext $socialAccountContext,
        Provider $provider,
        Request $request,
    ): JsonResponse {
        $guard = $this->guard($socialAccountContext);
        $data = $request->validate([
            'state' => ['required', 'string', 'size:55'],
            'code' => ['required', 'string'],
            'code_verifier' => ['required', 'string', 'regex:/^[A-Za-z0-9._~-]{43,128}$/D'],
        ]);

        try {
            $identity = $this->oauth->user(
                $socialAccountContext,
                $provider,
                $provider->frontendCallbackUrl(),
                new OAuthCallback($data['state'], $data['code_verifier'], $data['code']),
            );
            $user = $this->linker->findOrCreateUser($socialAccountContext, $provider, $identity);

            if (!$user->status->canAccess()) {
                throw new OAuthException('access_denied', 'The user cannot sign in.');
            }

            $token = $guard->claims(['social_account_context' => $socialAccountContext->name()])->login($user);

            return response()->json(new AuthResource($token, $user));
        } catch (OAuthException $e) {
            return response()->json(['message' => $e->getError()], 401);
        }
    }

    public function unlink(SocialAccountContext $socialAccountContext, Provider $provider): JsonResponse
    {
        $guard = $this->guard($socialAccountContext);
        /** @var User $user */
        $user = $guard->authenticate();
        abort_unless($user->status->canAccess(), 403);
        abort_unless($guard->payload()->get('social_account_context') === $socialAccountContext->name(), 403);
        abort_unless($provider->context === $socialAccountContext->name(), 404);
        SocialAccount::unlink($user, $provider);

        return response()->json(['unlinked' => true]);
    }

    private function guard(SocialAccountContext $context): JWTGuard
    {
        $guard = auth()->guard($context->guard());
        abort_unless($guard instanceof JWTGuard, 404);

        return $guard;
    }
}
