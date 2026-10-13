<?php

namespace PHPinnacle\Midas\Resources\Plans\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use PHPinnacle\Midas\Resources\Plans\PlanResource;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    public function getTitle(): string
    {
        return __('phpinnacle-midas::resources.payment_plan.pages.edit');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label(__('phpinnacle-midas::resources.payment_plan.actions.delete')),
        ];
    }
}
