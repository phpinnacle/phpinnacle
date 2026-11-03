<?php

namespace PHPinnacle\Cerber\Http\Controllers\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PHPinnacle\Cerber\Enums\LoginType;
use PHPinnacle\Cerber\Http\Resources\Auth\AuthResource;
use PHPinnacle\Cerber\Http\Resources\Auth\UserResource;
use PHPinnacle\Cerber\Models\Login;
use PHPinnacle\Cerber\Services\RegistrationService;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class RegistrationController
{
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
                Rule::unique(Login::class, 'value')->where('type', LoginType::Email->value)->whereNull('provider_id'),
            ],
            'password' => ['required', 'string', 'min:8'],
        ]);
        $user = $registration->register($data['email'], $data['password']);
        /** @var JWTGuard $guard */
        $guard = auth('api');

        return response()->json(new AuthResource($guard->login($user), $user), 201);
    }

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
