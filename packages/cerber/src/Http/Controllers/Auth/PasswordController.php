<?php

namespace PHPinnacle\Cerber\Http\Controllers\Auth;

use Filament\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use PHPinnacle\Cerber\Models\User;

class PasswordController
{
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
