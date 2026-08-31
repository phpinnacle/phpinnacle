<?php

namespace PHPinnacle\Cerber\Resources\Providers\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\Resources\Providers\ProviderResource;

/**
 * @property Provider $record
 */
class EditProvider extends EditRecord
{
    protected static string $resource = ProviderResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('phpinnacle-cerber::resources.provider.pages.edit');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_connection')
                ->label(__('phpinnacle-cerber::resources.provider.actions.test_connection'))
                ->icon('phosphor-play-circle')
                ->url(Filament::getCurrentPanel()->route('auth.test', $this->record->id))
                ->openUrlInNewTab(),
            DeleteAction::make()
                ->label(__('phpinnacle-cerber::resources.provider.actions.delete')),
        ];
    }
}
