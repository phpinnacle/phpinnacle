<?php

namespace PHPinnacle\Cerber\Resources\Providers\Pages;

use Filament\Resources\Pages\ListRecords;
use PHPinnacle\Cerber\Resources\Providers\ProviderResource;

class ListProviders extends ListRecords
{
    protected static string $resource = ProviderResource::class;

    public function getTitle(): string
    {
        return '';
    }
}
