<?php

namespace PHPinnacle\Cerber\Services;

use Filament\Auth\Notifications\ResetPassword;
use Filament\Auth\Notifications\VerifyEmail;
use Filament\Facades\Filament;
use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Support\Facades\URL;
use PHPinnacle\Cerber\Models\Login;
use PHPinnacle\Cerber\Models\User;

readonly class UserService
{
    public function __construct(
        private PasswordBrokerManager $passwords,
    ) {}

    public function resendVerification(Login $login): void
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

    public function resetPassword(User $user): string
    {
        return $this->passwords->broker(Filament::getAuthPasswordBroker())->sendResetLink(
            ['email' => $user->getEmailForPasswordReset()],
            function (User $user, #[\SensitiveParameter] string $token) {
                $notification = new ResetPassword($token);
                $notification->url = Filament::getResetPasswordUrl($token, $user);
                $user->notify($notification);
            },
        );
    }
}
