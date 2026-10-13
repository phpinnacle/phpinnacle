<?php

namespace PHPinnacle\Midas\Resources\Methods\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use PHPinnacle\Midas\Resources\Methods\MethodResource;

class EditMethod extends EditRecord
{
    protected static string $resource = MethodResource::class;

    public function getTitle(): string
    {
        return __('phpinnacle-midas::resources.payment_method.pages.edit');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label(__('phpinnacle-midas::resources.payment_method.actions.delete')),
        ];
    }
}
