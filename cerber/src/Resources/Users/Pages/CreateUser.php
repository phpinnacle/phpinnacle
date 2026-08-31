<?php

namespace PHPinnacle\Cerber\Resources\Users\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use PHPinnacle\Cerber\Resources\Users\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('phpinnacle-cerber::resources.user.pages.create');
    }
}
