<?php

namespace PHPinnacle\Cerber\Http\Controllers\Auth;

use Filament\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use OpenApi\Spec as OA;
use PHPinnacle\Cerber\Models\User;

class PasswordController
{
    #[OA\Operation\Post(
        path: '/auth/password/change',
        description: 'The first password does not require current. Existing JWTs are preserved and the temporary password marker is cleared.',
        summary: 'Change password',
        security: [new OA\Security\Requirement(scheme: 'cerberBearerAuth')],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\Schema(
                required: ['password'],
                properties: [
                    'current' => new OA\Schema(
                        description: 'Required when the user already has a password.',
                        type: 'string',
                        format: 'password',
                    ),
                    'password' => new OA\Schema(type: 'string', format: 'password', minLength: 8),
                ],
                type: 'object',
            ),
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 204, description: 'Password changed.'),
            new OA\Response(ref: '#/components/responses/CerberUnauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/CerberUnprocessableContent', response: 422),
        ],
    )]
    public function change(Request $request): Response
    {
        /** @var User $user */
        $user = auth('api')->user();
        /** @var array{password: string} $data */
        $data = $request->validate([
            'current' => $user->password !== null
                ? ['bail', 'required', 'string', 'current_password:api']
                : ['exclude'],
            'password' => ['required', 'string', 'min:8'],
        ]);
        $user->password = $data['password'];
        $user->save();

        return response()->noContent();
    }

    #[OA\Operation\Post(
        path: '/auth/password/forgot',
        description: 'Send password recovery mail. Unknown accounts and throttled mail receive the same response.',
        summary: 'Request password recovery',
        requestBody: new OA\RequestBody(
            description: 'Email identifying the account to recover.',
            required: true,
            content: new OA\Schema(
                required: ['email'],
                properties: [
                    'email' => new OA\Schema(
                        description: 'Email address',
                        type: 'string',
                        format: 'email',
                        example: 'user@example.com',
                    ),
                ],
                type: 'object',
            ),
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Recovery request accepted.',
                content: new OA\Schema(
                    properties: [
                        'message' => new OA\Schema(
                            type: 'string',
                            example: 'If an account exists, a password reset link has been sent.',
                        ),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                ref: '#/components/responses/CerberUnprocessableContent',
                response: 422,
            ),
        ],
    )]
    public function forgot(Request $request): JsonResponse
    {
        /** @var array{email: string} $data */
        $data = $request->validate(['email' => ['required', 'string', 'email', 'max:255']]);
        Password::broker()->sendResetLink($data, function (User $user, #[\SensitiveParameter] string $token) {
            $notification = new ResetPassword($token);
            $notification->url =
                rtrim(config('phpinnacle-cerber.api.frontend_url'), '/')
                . '/auth/reset-password?'
                . http_build_query([
                    'token' => $token,
                    'email' => $user->getEmailForPasswordReset(),
                ]);
            $user->notify($notification);
        });

        return response()->json(['message' => __('phpinnacle-cerber::auth.password_reset.sent')]);
    }

    #[OA\Operation\Post(
        path: '/auth/password/reset',
        description: 'Set the shared user password using a reset token. A successful reset consumes the token.',
        summary: 'Reset password',
        requestBody: new OA\RequestBody(
            description: 'Password reset credentials. Password confirmation is handled by the frontend.',
            required: true,
            content: new OA\Schema(
                required: ['token', 'email', 'password'],
                properties: [
                    'token' => new OA\Schema(
                        description: 'Reset token from the email',
                        type: 'string',
                        example: 'a3f1d2e5c8b9...',
                    ),
                    'email' => new OA\Schema(
                        description: 'Email address',
                        type: 'string',
                        format: 'email',
                        example: 'user@example.com',
                    ),
                    'password' => new OA\Schema(
                        description: 'New password with at least eight characters',
                        minLength: 8,
                        type: 'string',
                        format: 'password',
                        example: 'newpassword123',
                    ),
                ],
                type: 'object',
            ),
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password reset.',
                content: new OA\Schema(
                    properties: [
                        'message' => new OA\Schema(
                            type: 'string',
                            example: 'Password has been reset successfully.',
                        ),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                ref: '#/components/responses/CerberUnprocessableContent',
                response: 422,
            ),
        ],
    )]
    public function reset(Request $request): JsonResponse
    {
        /** @var array{token: string, email: string, password: string} $data */
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);
        $status = Password::broker()->reset($data, function (User $user, #[\SensitiveParameter] string $password) {
            $user->password = $password;
            $user->save();
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['token' => [__($status)]]);
        }

        return response()->json(['message' => __('phpinnacle-cerber::auth.password_reset.success')]);
    }
}
