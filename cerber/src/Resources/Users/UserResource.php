<?php

namespace PHPinnacle\Cerber\Resources\Users;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use PHPinnacle\Cerber\Models\User;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $isScopedToTenant = false;

    public static function getNavigationLabel(): string
    {
        return __('phpinnacle-cerber::resources.user.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('phpinnacle-cerber::resources.user.group');
    }

    public static function getNavigationIcon(): ?string
    {
        return config('phpinnacle-cerber.navigation.user.icon');
    }

    public static function getNavigationSort(): ?int
    {
        return config('phpinnacle-cerber.navigation.user.sort');
    }

    public static function form(Schema $schema): Schema
    {
        return Schemas\UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\UserTable::configure($table);
    }

    public static function getRelations(): array
    {
        return config('phpinnacle-cerber.resources.user.relations', []);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
