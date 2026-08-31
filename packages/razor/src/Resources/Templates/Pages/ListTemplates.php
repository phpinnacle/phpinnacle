<?php

namespace PHPinnacle\Razor\Resources\Templates\Pages;

use Filament\Resources\Pages\ListRecords;
use PHPinnacle\Razor\Resources\Templates\TemplateResource;

class ListTemplates extends ListRecords
{
    protected static string $resource = TemplateResource::class;

    public function getTitle(): string
    {
        return '';
    }
}
