<?php

namespace PHPinnacle\Cerber\Resources\Users\Pages;

use Filament\Resources\Pages\ListRecords;
use PHPinnacle\Cerber\Resources\Users\UserResource;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return '';
    }
}
