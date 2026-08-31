<?php

namespace PHPinnacle\Tags\Resources\Tags;

use Filament\Resources\Resource;
use PHPinnacle\Tags\Models\Tag;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static bool $isScopedToTenant = false;
}
