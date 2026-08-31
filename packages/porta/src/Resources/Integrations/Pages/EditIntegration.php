<?php

namespace PHPinnacle\Porta\Resources\Integrations\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use PHPinnacle\Porta\Resources\Integrations\Actions\TestAction;
use PHPinnacle\Porta\Resources\Integrations\IntegrationResource;

class EditIntegration extends EditRecord
{
    protected static string $resource = IntegrationResource::class;

    public static function getNavigationLabel(): string
    {
        return __('phpinnacle-porta::resources.integration.pages.edit');
    }

    public function getTitle(): string
    {
        return self::getNavigationLabel();
    }

    protected function getHeaderActions(): array
    {
        return [
            TestAction::make(),
            DeleteAction::make()
                ->label(__('phpinnacle-porta::resources.integration.actions.delete')),
        ];
    }
}
