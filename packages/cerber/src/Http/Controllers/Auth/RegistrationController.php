<?php

namespace PHPinnacle\Cerber\Http\Controllers\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Spec as OA;
use PHPinnacle\Cerber\Enums\LoginType;
use PHPinnacle\Cerber\Http\Resources\Auth\AuthResource;
use PHPinnacle\Cerber\Http\Resources\Auth\UserResource;
use PHPinnacle\Cerber\Models\Login;
use PHPinnacle\Cerber\Services\RegistrationService;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class RegistrationController
{
    #[OA\Operation\Post(
        path: '/auth/register',
        description: 'Create a user and an unverified email login atomically, send verification mail after commit, and immediately issue a JWT.',
        summary: 'Register',
        requestBody: new OA\RequestBody(
            description: 'Registration credentials. Password confirmation is handled by the frontend.',
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
                        description: 'Password with at least eight characters',
                        minLength: 8,
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
                response: 201,
                description: 'Registered successfully.',
                content: new OA\Schema(ref: '#/components/schemas/CerberAuthResource'),
            ),
            new OA\Response(
                ref: '#/components/responses/CerberUnprocessableContent',
                response: 422,
            ),
        ],
    )]
    public function register(Request $request, RegistrationService $registration): JsonResponse
    {
        $email = $request->input('email');
        if (is_string($email)) {
            $request->merge(['email' => LoginType::Email->canonical($email)]);
        }

        /** @var array{email: string, password: string} $data */
        $data = $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(Login::class, 'value')->whereNull('provider_id'),
            ],
            'password' => ['required', 'string', 'min:8'],
        ]);
        $user = $registration->register($data['email'], $data['password']);
        /** @var JWTGuard $guard */
        $guard = auth('api');

        return response()->json(new AuthResource($guard->login($user), $user), 201);
    }

    #[OA\Operation\Get(
        path: '/auth/verify-email',
        description: 'Forward the complete query string from the verification email unchanged. Laravel signs the link, which expires after one hour.',
        summary: 'Verify email',
        tags: ['Auth'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Login UUID from the verification email.',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
            new OA\Parameter(
                name: 'hash',
                description: 'Email hash from the signed link.',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', maxLength: 64, minLength: 64),
            ),
            new OA\Parameter(name: 'expires', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'signature', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email verified.',
                content: new OA\Schema(ref: '#/components/schemas/CerberUserResource'),
            ),
            new OA\Response(
                ref: '#/components/responses/CerberNotFound',
                response: 404,
            ),
            new OA\Response(
                ref: '#/components/responses/CerberUnprocessableContent',
                response: 422,
            ),
            new OA\Response(ref: '#/components/responses/CerberForbidden', response: 403),
        ],
    )]
    public function verify(Request $request): JsonResponse
    {
        /** @var array{id: string, hash: string} $data */
        $data = $request->validate([
            'id' => ['required', 'string', 'uuid'],
            'hash' => ['required', 'string', 'size:64'],
        ]);
        $login = Login::query()->findOrFail($data['id']);
        abort_unless($login->matchesVerificationHash($data['hash']), 403);
        $login->markVerified();

        return response()->json(new UserResource($login->user));
    }
}
