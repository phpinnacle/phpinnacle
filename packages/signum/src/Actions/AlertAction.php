<?php

namespace PHPinnacle\Signum\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Signum\Enums\Level;
use PHPinnacle\Signum\Models\Alert;
use PHPinnacle\Signum\Resources\Alerts\AlertResource;

class AlertAction extends Action
{
    public static function getDefaultName(): string
    {
        return 'alert';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-signum::actions.alert.label'))
            ->modalHeading(__('phpinnacle-signum::actions.alert.modal.heading'))
            ->modalDescription(__('phpinnacle-signum::actions.alert.modal.description'))
            ->modalIcon('phosphor-warning')
            ->color('gray')
            ->icon('phosphor-warning')
            ->slideOver()
            ->schema([
                TextInput::make('title')
                    ->label(__('phpinnacle-signum::actions.alert.fields.title'))
                    ->minLength(1)
                    ->maxLength(255)
                    ->required(),
                ToggleButtons::make('level')
                    ->label(__('phpinnacle-signum::actions.alert.fields.level'))
                    ->options(Level::class)
                    ->default(Level::Info)
                    ->enum(Level::class)
                    ->grouped()
                    ->required(),
                Textarea::make('message')
                    ->label(__('phpinnacle-signum::actions.alert.fields.message'))
                    ->rows(6),
            ])
            ->action(Alert::create(...))
            ->visible(AlertResource::canCreate(...));
    }
}
