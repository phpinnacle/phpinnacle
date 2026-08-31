<?php

namespace PHPinnacle\Porta\Resources\Integrations\Pages;

use Filament\Resources\Pages\ListRecords;
use PHPinnacle\Porta\Resources\Integrations\IntegrationResource;

class ListIntegrations extends ListRecords
{
    protected static string $resource = IntegrationResource::class;

    public function getTitle(): string
    {
        return '';
    }
}
