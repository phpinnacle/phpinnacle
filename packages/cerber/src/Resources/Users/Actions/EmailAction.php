<?php

namespace PHPinnacle\Cerber\Resources\Users\Actions;

use Filament\Actions\Action;
use PHPinnacle\Cerber\Models\User;
use PHPinnacle\Cerber\Services\UserService;

class EmailAction
{
    public static function dropVerification(): Action
    {
        return Action::make('drop_verification')
            ->label(__('phpinnacle-cerber::resources.user.actions.drop_verification'))
            ->icon('phosphor-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->action(fn (User $record) => $record->dropVerification())
            ->visible(fn (?User $record) => $record?->hasVerifiedEmail());
    }

    public static function markAsVerified(): Action
    {
        return Action::make('mark_as_verified')
            ->label(__('phpinnacle-cerber::resources.user.actions.mark_as_verified'))
            ->icon('phosphor-check-circle')
            ->requiresConfirmation()
            ->action(fn (User $record) => $record->markEmailAsVerified())
            ->visible(fn (?User $record) => $record && !$record->hasVerifiedEmail());
    }

    public static function resendVerification(): Action
    {
        return Action::make('resend_verification')
            ->label(__('phpinnacle-cerber::resources.user.actions.resend_verification'))
            ->icon('phosphor-envelope')
            ->requiresConfirmation()
            ->action(fn (UserService $service, User $record) => $service->resendVerification($record))
            ->visible(fn (?User $record) => $record && !$record->hasVerifiedEmail());
    }
}
