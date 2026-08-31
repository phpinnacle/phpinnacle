<?php

namespace PHPinnacle\Pinax\Resources\Media;

use Filament\Resources\Resource;
use PHPinnacle\Pinax\Models\Media;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static bool $shouldSkipAuthorization = true;
}
