<?php

namespace PHPinnacle\Cerber\Resources\Users\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use PHPinnacle\Cerber\Models\Login;
use PHPinnacle\Cerber\Services\UserService;

class PasswordAction
{
    public static function reset(): Action
    {
        return Action::make('resetPassword')
            ->icon(Heroicon::Key)
            ->label(__('phpinnacle-cerber::logins.reset_password'))
            ->requiresConfirmation()
            ->action(function (Login $record, UserService $service) {
                $status = $service->resetPassword($record->user);
                Notification::make()->title(__($status))->send();
            });
    }
}
