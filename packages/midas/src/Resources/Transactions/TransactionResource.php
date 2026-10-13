<?php

namespace PHPinnacle\Midas\Resources\Transactions;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Midas\Models\Transaction;

/** @extends Resource<Transaction> */
class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $recordTitleAttribute = 'number';

    public static function getModelLabel(): string
    {
        return __('phpinnacle-midas::resources.transaction.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('phpinnacle-midas::resources.transaction.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('phpinnacle-midas::resources.transaction.group');
    }

    public static function getNavigationIcon(): ?string
    {
        return config('phpinnacle-midas.navigation.transaction.icon');
    }

    public static function getNavigationSort(): ?int
    {
        return config('phpinnacle-midas.navigation.transaction.sort');
    }

    public static function table(Table $table): Table
    {
        return Tables\TransactionsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\TransactionInfolist::configure($schema);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [RelationManagers\OperationsRelationManager::class];
    }
}
