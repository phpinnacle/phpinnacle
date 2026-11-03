<?php

namespace PHPinnacle\Cerber\Services;

use Filament\Auth\Notifications\ResetPassword;
use Filament\Auth\Notifications\VerifyEmail;
use Filament\Facades\Filament;
use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Support\Facades\URL;
use PHPinnacle\Cerber\Enums\LoginType;
use PHPinnacle\Cerber\Models\Login;
use PHPinnacle\Cerber\Models\User;

readonly class LoginService
{
    public function __construct(
        private PasswordBrokerManager $passwords,
    ) {}

    public function sendVerification(Login $login): void
    {
        if (!$login->requiresVerification()) {
            return;
        }

        $panel = Filament::getCurrentOrDefaultPanel();
        $notification = new VerifyEmail;
        $notification->url = URL::temporarySignedRoute(
            'filament.' . $panel->getId() . '.cerber.logins.verify',
            now()->addHour(),
            ['login' => $login->id, 'hash' => hash('sha256', $login->value)],
        );
        $login->notify($notification->afterCommit());
    }

    public function resetPassword(Login $login): string
    {
        if ($login->type !== LoginType::Email) {
            return PasswordBroker::INVALID_USER;
        }

        return $this->passwords->broker(Filament::getAuthPasswordBroker())->sendResetLink(
            ['type' => LoginType::Email->value, 'value' => $login->value],
            function (User $user, #[\SensitiveParameter] string $token) {
                $notification = new ResetPassword($token);
                $notification->url = Filament::getResetPasswordUrl($token, $user);
                $user->notify($notification);
            },
        );
    }
}
