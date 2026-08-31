<?php

namespace PHPinnacle\Signum\Resources\Alerts;

use Filament\Resources\Resource;
use PHPinnacle\Signum\Models\Alert;

class AlertResource extends Resource
{
    protected static ?string $model = Alert::class;

    protected static bool $shouldRegisterNavigation = false;
}
