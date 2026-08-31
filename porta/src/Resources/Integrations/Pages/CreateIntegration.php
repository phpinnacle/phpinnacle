<?php

namespace PHPinnacle\Porta\Resources\Integrations\Pages;

use Filament\Resources\Pages\CreateRecord;
use PHPinnacle\Porta\Resources\Integrations\IntegrationResource;

class CreateIntegration extends CreateRecord
{
    protected static string $resource = IntegrationResource::class;

    public function getTitle(): string
    {
        return __('phpinnacle-porta::resources.integration.pages.create');
    }
}
