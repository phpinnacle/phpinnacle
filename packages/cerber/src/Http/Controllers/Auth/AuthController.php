<?php

namespace PHPinnacle\Cerber\Http\Controllers\Auth;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPinnacle\Cerber\Http\Resources\Auth\AuthResource;
use PHPinnacle\Cerber\Http\Resources\Auth\UserResource;
use PHPinnacle\Cerber\Models\User;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AuthController
{
    public function login(Request $request): JsonResponse
    {
        /** @var array{email: string, password: string} $credentials */
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);
        /** @var JWTGuard $guard */
        $guard = auth('api');
        $token = $guard->attempt($credentials);

        if ($token === false) {
            throw new AuthenticationException;
        }

        /** @var User $user */
        $user = $guard->user();

        return response()->json(new AuthResource($token, $user));
    }

    public function refresh(): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');
        /** @var User $user */
        $user = $guard->user();

        return response()->json(new AuthResource($guard->refresh(), $user));
    }

    public function logout(): Response
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');
        $guard->logout();

        return response()->noContent();
    }

    public function profile(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        return response()->json(new UserResource($user));
    }
}
