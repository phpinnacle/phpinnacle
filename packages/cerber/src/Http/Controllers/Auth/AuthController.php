<?php

namespace PHPinnacle\Cerber\Http\Controllers\Auth;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Spec as OA;
use PHPinnacle\Cerber\Http\Resources\Auth\AuthResource;
use PHPinnacle\Cerber\Http\Resources\Auth\UserResource;
use PHPinnacle\Cerber\Models\User;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AuthController
{
    #[OA\Operation\Post(
        path: '/auth/login',
        description: 'Sign in with a verified email login and the shared user password.',
        summary: 'Sign in',
        requestBody: new OA\RequestBody(
            description: 'Sign-in credentials.',
            required: true,
            content: new OA\Schema(
                required: ['email', 'password'],
                properties: [
                    'email' => new OA\Schema(
                        description: 'Email address',
                        type: 'string',
                        format: 'email',
                        example: 'user@example.com',
                    ),
                    'password' => new OA\Schema(
                        description: 'Shared user password',
                        type: 'string',
                        format: 'password',
                        example: 'secret123',
                    ),
                ],
                type: 'object',
            ),
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Signed in successfully.',
                content: new OA\Schema(ref: '#/components/schemas/CerberAuthResource'),
            ),
            new OA\Response(
                ref: '#/components/responses/CerberUnauthorized',
                response: 401,
            ),
            new OA\Response(
                ref: '#/components/responses/CerberUnprocessableContent',
                response: 422,
            ),
        ],
    )]
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

    #[OA\Operation\Post(
        path: '/auth/refresh',
        description: 'Rotate an unexpired JWT.',
        summary: 'Refresh token',
        security: [new OA\Security\Requirement(scheme: 'cerberBearerAuth')],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token refreshed.',
                content: new OA\Schema(ref: '#/components/schemas/CerberAuthResource'),
            ),
            new OA\Response(
                ref: '#/components/responses/CerberUnauthorized',
                response: 401,
            ),
        ],
    )]
    public function refresh(): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');
        /** @var User $user */
        $user = $guard->user();

        return response()->json(new AuthResource($guard->refresh(), $user));
    }

    #[OA\Operation\Post(
        path: '/auth/logout',
        description: 'Invalidate the current JWT when blacklisting is enabled.',
        summary: 'Sign out',
        security: [new OA\Security\Requirement(scheme: 'cerberBearerAuth')],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Signed out.',
            ),
        ],
    )]
    public function logout(): Response
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');
        $guard->logout();

        return response()->noContent();
    }

    #[OA\Operation\Get(
        path: '/profile',
        description: 'Return the authenticated user profile. Email fields come from the default email login.',
        summary: 'Profile',
        security: [new OA\Security\Requirement(scheme: 'cerberBearerAuth')],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User profile',
                content: new OA\Schema(ref: '#/components/schemas/CerberUserResource'),
            ),
            new OA\Response(
                ref: '#/components/responses/CerberUnauthorized',
                response: 401,
            ),
        ],
    )]
    public function profile(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        return response()->json(new UserResource($user));
    }
}
