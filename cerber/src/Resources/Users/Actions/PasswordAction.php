<?php

namespace PHPinnacle\Cerber\Resources\Users\Actions;

use Filament\Actions\Action;
use PHPinnacle\Cerber\Models\User;
use PHPinnacle\Cerber\Services\UserService;

class PasswordAction
{
    public static function reset(): Action
    {
        return Action::make('reset_password')
            ->label(__('phpinnacle-cerber::resources.user.actions.reset_password'))
            ->icon('phosphor-lock-open')
            ->color('warning')
            ->requiresConfirmation()
            ->action(fn (UserService $service, User $record) => $service->resetPassword($record))
            ->visible(fn (?User $record) => $record !== null);
    }
}
