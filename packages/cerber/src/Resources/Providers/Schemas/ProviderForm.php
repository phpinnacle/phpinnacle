<?php

namespace PHPinnacle\Cerber\Resources\Providers\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use PHPinnacle\Cerber\Enums\ProviderScope;
use PHPinnacle\Cerber\Forms\ProviderSelect;
use PHPinnacle\Common\Forms\ActiveSelect;

class ProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('phpinnacle-cerber::resources.provider.sections.general'))
                    ->columns(4)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('phpinnacle-cerber::resources.provider.fields.name'))
                            ->columnSpan(2)
                            ->maxLength(255)
                            ->required(),

                        ProviderSelect::make('type')
                            ->label(__('phpinnacle-cerber::resources.provider.fields.type'))
                            ->disabledOn('edit')
                            ->scopedUnique()
                            ->required()
                            ->live(),

                        ActiveSelect::make(),

                        CheckboxList::make('scopes')
                            ->label(__('phpinnacle-cerber::resources.provider.fields.scopes'))
                            ->options(ProviderScope::class)
                            ->default([ProviderScope::Session->value])
                            ->required()
                            ->columns(3)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('phpinnacle-cerber::resources.provider.sections.configuration'))
                    ->columns()
                    ->statePath('config')
                    ->schema([
                        TextInput::make('client_id')
                            ->label(__('phpinnacle-cerber::resources.provider.fields.client_id'))
                            ->maxLength(255)
                            ->required(),

                        TextInput::make('client_secret')
                            ->label(__('phpinnacle-cerber::resources.provider.fields.client_secret'))
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->required(),

                        TagsInput::make('oauth_scopes')
                            ->label(__('phpinnacle-cerber::resources.provider.fields.oauth_scopes'))
                            ->placeholder(__('phpinnacle-cerber::resources.provider.placeholders.oauth_scopes'))
                            ->columnSpanFull(),

                        KeyValue::make('additional')
                            ->label(__('phpinnacle-cerber::resources.provider.fields.additional'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
