<?php

namespace PHPinnacle\Razor\Resources\Templates\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use PHPinnacle\Razor\Forms\Editor;
use PHPinnacle\Razor\Services\Registry;

class TemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('phpinnacle-razor::resources.template.sections.general'))
                    ->columns(4)
                    ->schema(self::common()),
                Editor::make('content')
                    ->columnSpanFull()
                    ->hiddenLabel(),
            ]);
    }

    public static function common(): array
    {
        return [
            TextInput::make('name')
                ->label(__('phpinnacle-razor::resources.template.fields.name'))
                ->maxLength(255)
                ->required(),
            TextInput::make('numeration')
                ->label(__('phpinnacle-razor::resources.template.fields.numeration'))
                ->maxLength(255),
            Select::make('section')
                ->label(__('phpinnacle-razor::resources.template.fields.section'))
                ->options(fn (Registry $registry) => Arr::pluck($registry->all(), 'label', 'key'))
                ->disabledOn('edit')
                ->required()
                ->live(),
            Select::make('is_active')
                ->label(__('phpinnacle-razor::resources.template.fields.is_active'))
                ->prefixIcon('phosphor-check-circle')
                ->boolean()
                ->default(true)
                ->selectablePlaceholder(false),
        ];
    }
}
