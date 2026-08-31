<?php

namespace PHPinnacle\Cerber\Resources\Users\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use PHPinnacle\Cerber\Resources\Users\Actions\ImpersonateAction;
use PHPinnacle\Cerber\Resources\Users\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('phpinnacle-cerber::resources.user.pages.edit');
    }

    protected function getHeaderActions(): array
    {
        return [
            ImpersonateAction::make(),
            DeleteAction::make()
                ->label(__('phpinnacle-cerber::resources.user.actions.delete')),
        ];
    }
}
