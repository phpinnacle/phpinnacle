<?php

namespace PHPinnacle\Cerber\Services;

use Filament\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use PHPinnacle\Cerber\Enums\LoginType;
use PHPinnacle\Cerber\Models\Login;
use PHPinnacle\Cerber\Models\User;

class RegistrationService
{
    public function register(string $email, #[\SensitiveParameter] string $password): User
    {
        /** @var class-string<User> $model */
        $model = config('phpinnacle-cerber.user_model');
        $user = $model::register(Str::before($email, '@'), ['web']);

        return $user->getConnection()->transaction(function () use ($user, $email, $password) {
            $user->password = $password;
            $user->save();
            $login = Login::register($user, LoginType::Email, $email);
            $signedUrl = URL::temporarySignedRoute(
                'cerber.api.verify-email',
                now()->addHour(),
                ['id' => $login->id, 'hash' => $login->verificationHash()],
                absolute: false,
            );
            $notification = new VerifyEmail;
            $notification->url =
                rtrim(config('phpinnacle-cerber.api.frontend_url'), '/')
                . '/auth/verify-email?'
                . parse_url($signedUrl, PHP_URL_QUERY);
            $login->notify($notification->afterCommit());

            return $user;
        });
    }
}
