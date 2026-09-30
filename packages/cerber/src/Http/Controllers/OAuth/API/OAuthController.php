<?php

namespace PHPinnacle\Cerber\Http\Controllers\OAuth\API;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PHPinnacle\Cerber\Enums\ProviderScope;
use PHPinnacle\Cerber\Exceptions\OAuthException;
use PHPinnacle\Cerber\Http\Resources\OAuth\OAuthProviderResource;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\OAuth\CallbackData;
use PHPinnacle\Cerber\OAuth\OAuthFactory;

class OAuthController extends Controller
{
    public function __construct(
        private readonly OAuthFactory $factory,
    ) {}

    public function providers(): JsonResponse
    {
        return response()->json(
            OAuthProviderResource::collection(Provider::valid(ProviderScope::Stateless)),
        );
    }

    public function redirect(Provider $provider, Request $request): JsonResponse
    {
        try {
            $response = $this->factory
                ->make(ProviderScope::Stateless)
                ->redirect($provider);
        } catch (OAuthException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'redirect_url' => $response->getTargetUrl(),
        ]);
    }

    public function callback(Provider $provider, Request $request): JsonResponse
    {
        try {
            return $this->factory
                ->make(ProviderScope::Stateless)
                ->callback($provider, CallbackData::fromRequest($request));
        } catch (OAuthException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }
}
