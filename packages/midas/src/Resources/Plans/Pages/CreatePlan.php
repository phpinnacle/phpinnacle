<?php

namespace PHPinnacle\Midas\Resources\Plans\Pages;

use Filament\Resources\Pages\CreateRecord;
use PHPinnacle\Midas\Resources\Plans\PlanResource;

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;

    public function getTitle(): string
    {
        return __('phpinnacle-midas::resources.payment_plan.pages.create');
    }
}
