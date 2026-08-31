<?php

namespace PHPinnacle\Casus\Resources\Exceptions;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use PHPinnacle\Casus\Models\Exception as ExceptionRecord;

class ExceptionResource extends Resource
{
    protected static ?string $model = ExceptionRecord::class;

    protected static bool $isScopedToTenant = false;

    public static function getNavigationGroup(): string
    {
        return __('phpinnacle-casus::resources.exception.group');
    }

    public static function getNavigationIcon(): ?string
    {
        return config('phpinnacle-casus.navigation.exception.icon', 'phosphor-bug');
    }

    public static function getNavigationLabel(): string
    {
        return __('phpinnacle-casus::resources.exception.label');
    }

    public static function getNavigationSort(): ?int
    {
        return config('phpinnacle-casus.navigation.exception.sort');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExceptions::route('/'),
            'view' => Pages\ViewException::route('/{record}'),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\ExceptionInfo::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\ExceptionsTable::configure($table);
    }
}
