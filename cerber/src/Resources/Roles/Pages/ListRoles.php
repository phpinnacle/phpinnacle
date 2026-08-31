<?php

namespace PHPinnacle\Cerber\Resources\Roles\Pages;

use Filament\Resources\Pages\ListRecords;
use PHPinnacle\Cerber\Resources\Roles\RoleResource;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    public function getTitle(): string
    {
        return '';
    }
}
