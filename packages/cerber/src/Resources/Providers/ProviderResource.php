<?php

namespace PHPinnacle\Cerber\Resources\Providers;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use PHPinnacle\Cerber\Models\Provider;

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('phpinnacle-cerber::resources.provider.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('phpinnacle-cerber::resources.provider.group');
    }

    public static function getNavigationIcon(): ?string
    {
        return config('phpinnacle-cerber.navigation.provider.icon');
    }

    public static function getNavigationSort(): ?int
    {
        return config('phpinnacle-cerber.navigation.provider.sort');
    }

    public static function form(Schema $schema): Schema
    {
        return Schemas\ProviderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\ProviderTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProviders::route('/'),
            'create' => Pages\CreateProvider::route('/create'),
            'edit' => Pages\EditProvider::route('/{record}/edit'),
        ];
    }
}
