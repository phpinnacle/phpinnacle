<?php

namespace PHPinnacle\Casus\Resources\Exceptions\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use PHPinnacle\Casus\Models\Exception as ExceptionRecord;
use PHPinnacle\Casus\Resources\Exceptions\ExceptionResource;

/**
 * @property ExceptionRecord $record
 */
class ViewException extends ViewRecord
{
    protected static string $resource = ExceptionResource::class;

    public function getTitle(): string
    {
        return __('phpinnacle-casus::resources.exception.pages.view', [
            'type' => $this->record->type,
            'code' => $this->record->code,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
