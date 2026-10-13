<?php

namespace PHPinnacle\Midas\Resources\Transactions\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Midas\Resources\Transactions\TransactionResource;

class OperationsRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $relatedResource = TransactionResource::class;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('phpinnacle-midas::resources.transaction.sections.operations');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('phpinnacle-midas::resources.transaction.sections.operations'))
            ->emptyStateHeading(__('phpinnacle-midas::resources.transaction.empty.operations'))
            ->emptyStateDescription(null);
    }
}
