<?php

namespace PHPinnacle\Midas\Resources\Plans\Pages;

use Filament\Resources\Pages\ListRecords;
use PHPinnacle\Midas\Resources\Plans\PlanResource;

class ListPlans extends ListRecords
{
    protected static string $resource = PlanResource::class;

    public function getTitle(): string
    {
        return '';
    }
}
