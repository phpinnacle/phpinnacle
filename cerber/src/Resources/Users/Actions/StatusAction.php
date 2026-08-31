<?php

namespace PHPinnacle\Cerber\Resources\Users\Actions;

use Filament\Actions\Action;
use PHPinnacle\Cerber\Enums\UserStatus;
use PHPinnacle\Cerber\Models\User;

class StatusAction extends Action
{
    public static function getDefaultName(): string
    {
        return 'cerber_user_status';
    }

    public function setUp(): void
    {
        $this
            ->icon(fn (User $record) => match ($record->status) {
                UserStatus::Active => UserStatus::Archived->getIcon(),
                UserStatus::Archived, UserStatus::Blocked => UserStatus::Active->getIcon(),
            })
            ->color(fn (User $record) => match ($record->status) {
                UserStatus::Active => UserStatus::Archived->getColor(),
                UserStatus::Archived, UserStatus::Blocked => UserStatus::Active->getColor(),
            })
            ->label(fn (User $record) => match ($record->status) {
                UserStatus::Active => __('phpinnacle-cerber::resources.user.actions.archive'),
                UserStatus::Archived, UserStatus::Blocked => __('phpinnacle-cerber::resources.user.actions.restore'),
            })
            ->action(function (User $record) {
                $record->status = $record->status === UserStatus::Active
                    ? UserStatus::Archived
                    : UserStatus::Active;

                $record->save();
            });
    }
}
