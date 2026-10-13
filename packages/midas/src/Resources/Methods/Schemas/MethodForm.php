<?php

namespace PHPinnacle\Midas\Resources\Methods\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use PHPinnacle\Common\Forms\ActiveSelect;
use PHPinnacle\Midas\Forms\ProviderSelect;
use PHPinnacle\Midas\Models\PaymentMethod;
use PHPinnacle\Midas\Services\ProviderRegistry;

class MethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components(function (ProviderRegistry $registry, ?PaymentMethod $record) {
                $provider = $record !== null ? $registry->get($record->provider) : null;
                $schema = $provider?->form() ?? [];

                return [
                    Section::make()
                        ->heading(__('phpinnacle-midas::resources.payment_method.sections.general'))
                        ->columns(4)
                        ->schema([
                            TextInput::make('name')
                                ->label(__('phpinnacle-midas::resources.payment_method.fields.name'))
                                ->columnSpan(2)
                                ->minLength(1)
                                ->maxLength(255)
                                ->required(),
                            ProviderSelect::make('provider')
                                ->label(__('phpinnacle-midas::resources.payment_method.fields.provider'))
                                ->disabled(),
                            ActiveSelect::make()
                                ->disabled($record && $record->is_default),
                        ]),
                    Section::make()
                        ->heading(__('phpinnacle-midas::resources.payment_method.sections.options'))
                        ->statePath('settings')
                        ->schema($schema)
                        ->visible($schema !== []),
                ];
            });
    }
}
