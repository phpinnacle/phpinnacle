<?php

namespace PHPinnacle\Cerber\Resources\Roles\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use PHPinnacle\Cerber\Resources\Roles\RoleResource;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('phpinnacle-cerber::resources.role.pages.create');
    }
}
