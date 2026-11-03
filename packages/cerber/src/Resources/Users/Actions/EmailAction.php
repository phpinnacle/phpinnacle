<?php

namespace PHPinnacle\Cerber\Resources\Users\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use PHPinnacle\Cerber\Models\Login;
use PHPinnacle\Cerber\Services\UserService;

class EmailAction
{
    public static function resendVerification(): Action
    {
        return Action::make('resendVerification')
            ->icon(Heroicon::Envelope)
            ->label(__('phpinnacle-cerber::logins.resend_verification'))
            ->visible(fn (Login $record) => $record->requiresVerification())
            ->successNotificationTitle(__('phpinnacle-cerber::logins.verification_sent'))
            ->action(function (Login $record, UserService $service, Action $action) {
                $service->resendVerification($record);
                $action->success();
            });
    }

    public static function markAsVerified(): Action
    {
        return Action::make('verify')
            ->icon(Heroicon::CheckBadge)
            ->label(__('phpinnacle-cerber::resources.user.actions.mark_as_verified'))
            ->visible(fn (Login $record) => $record->requiresVerification())
            ->requiresConfirmation()
            ->successNotificationTitle(__('phpinnacle-cerber::logins.verified'))
            ->action(function (Login $record, Action $action) {
                $record->markVerified();
                $action->success();
            });
    }

    public static function dropVerification(): Action
    {
        return Action::make('dropVerification')
            ->icon(Heroicon::ShieldExclamation)
            ->label(__('phpinnacle-cerber::resources.user.actions.drop_verification'))
            ->visible(fn (Login $record) => $record->verified_at !== null)
            ->requiresConfirmation()
            ->successNotificationTitle(__('phpinnacle-cerber::logins.saved'))
            ->action(function (Login $record, Action $action) {
                $record->dropVerification();
                $action->success();
            });
    }
}
