<?php

namespace PHPinnacle\Cerber\Resources\Providers\Pages;

use Filament\Resources\Pages\CreateRecord;
use PHPinnacle\Cerber\Resources\Providers\ProviderResource;

class CreateProvider extends CreateRecord
{
    protected static string $resource = ProviderResource::class;

    public function getTitle(): string
    {
        return __('phpinnacle-cerber::resources.provider.pages.create');
    }
}
