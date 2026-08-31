<?php

namespace PHPinnacle\Cerber\Resources\Roles;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use PHPinnacle\Cerber\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return Schemas\RoleForm::configure($schema);
    }

    public static function getNavigationGroup(): string
    {
        return __('phpinnacle-cerber::resources.role.group');
    }

    public static function getNavigationIcon(): ?string
    {
        return config('phpinnacle-cerber.navigation.role.icon');
    }

    public static function getNavigationLabel(): string
    {
        return __('phpinnacle-cerber::resources.role.label');
    }

    public static function getNavigationSort(): ?int
    {
        return config('phpinnacle-cerber.navigation.role.sort');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function table(Table $table): Table
    {
        return Tables\RoleTable::configure($table);
    }
}
