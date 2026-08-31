<?php

namespace PHPinnacle\Signum\Components;

use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Group;
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Signum\Actions\DismissAction;
use PHPinnacle\Signum\Models\Alert;

class Alerts extends Group
{
    public function setUp(): void
    {
        parent::setUp();

        $this
            ->schema(fn (?Model $record) => Alert::list($record)->map($this->callout(...))->all())
            ->visible(fn (Alerts $component) => !empty($component->getChildComponents()))
            ->columnSpanFull();
    }

    private function callout(Alert $alert): Callout
    {
        return Callout::make()
            ->heading($alert->getLabel())
            ->description($alert->getDescription())
            ->icon($alert->getIcon())
            ->color($alert->getColor())
            ->controlActions([
                DismissAction::make(sprintf('dismiss:%s', $alert->getKey()))
                    ->record($alert),
            ]);
    }
}
