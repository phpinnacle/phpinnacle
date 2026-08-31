<?php

namespace PHPinnacle\Casus\Resources\Exceptions\Pages;

use Filament\Resources\Pages\ListRecords;
use PHPinnacle\Casus\Resources\Exceptions\ExceptionResource;

class ListExceptions extends ListRecords
{
    protected static string $resource = ExceptionResource::class;

    public function getTitle(): string
    {
        return '';
    }
}
