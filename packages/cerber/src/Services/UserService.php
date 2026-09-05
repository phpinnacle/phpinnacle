<?php

namespace PHPinnacle\Cerber\Services;

use Filament\Auth\Notifications\ResetPassword;
use Filament\Auth\Notifications\VerifyEmail;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Contracts\Auth\PasswordBroker;
use PHPinnacle\Cerber\Models\User;

readonly class UserService
{
    public function __construct(
        private PasswordBrokerManager $passwords,
    ) {}

    public function resendVerification(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $notification = new VerifyEmail;
        $notification->url = Filament::getVerifyEmailUrl($user);

        $user->notify($notification);

        Notification::make()
            ->title(__(
                'filament-panels::pages/auth/email-verification/email-verification-prompt.notifications.notification_resent.title',
            ))
            ->success()
            ->send();
    }

    public function resetPassword(User $user): void
    {
        if (empty($user->email)) {
            return;
        }

        $broker = $this->passwords->broker(Filament::getAuthPasswordBroker());
        $status = $broker->sendResetLink([
            'email' => $user->email,
        ], function (User $user, #[\SensitiveParameter] string $token) {
            $notification = new ResetPassword($token);
            $notification->url = Filament::getResetPasswordUrl($token, $user);

            $user->notify($notification);
        });

        if ($status !== PasswordBroker::RESET_LINK_SENT) {
            Notification::make()
                ->title(__($status))
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title(__($status))
            ->success()
            ->send();
    }
}
