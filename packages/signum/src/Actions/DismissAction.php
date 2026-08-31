<?php

namespace PHPinnacle\Signum\Actions;

use Filament\Actions\Action;
use PHPinnacle\Signum\Models\Alert;
use PHPinnacle\Signum\Resources\Alerts\AlertResource;

class DismissAction extends Action
{
    public static function getDefaultName(): string
    {
        return 'dismiss';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-signum::actions.dismiss.label'))
            ->icon('phosphor-x')
            ->iconButton()
            ->color(fn (Alert $record) => $record->getColor())
            ->action(fn (Alert $record) => $record->delete())
            ->visible(fn (Alert $record) => AlertResource::canDelete($record));
    }
}
