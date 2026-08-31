<?php

namespace PHPinnacle\Razor\Resources\Templates;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use PHPinnacle\Razor\Models\Template;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;

    public static function form(Schema $schema): Schema
    {
        return Schemas\TemplateForm::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_history', false);
    }

    public static function getNavigationGroup(): string
    {
        return __('phpinnacle-razor::resources.template.group');
    }

    public static function getNavigationIcon(): ?string
    {
        return config('phpinnacle-razor.navigation.template.icon');
    }

    public static function getNavigationLabel(): string
    {
        return __('phpinnacle-razor::resources.template.label');
    }

    public static function getNavigationSort(): ?int
    {
        return config('phpinnacle-razor.navigation.template.sort');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTemplates::route('/'),
            'edit' => Pages\EditTemplate::route('/{record}/edit'),
        ];
    }

    public static function table(Table $table): Table
    {
        return Tables\TemplatesTable::configure($table);
    }
}
