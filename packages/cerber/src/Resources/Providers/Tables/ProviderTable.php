<?php

namespace PHPinnacle\Cerber\Resources\Providers\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\Resources\Providers\ProviderResource;
use PHPinnacle\Cerber\Services\ProviderRegistry;
use PHPinnacle\Common\Tables\ActiveColumn;
use PHPinnacle\Common\Tables\CreatedColumn;
use PHPinnacle\Common\Tables\UpdatedColumn;

class ProviderTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading(__('phpinnacle-cerber::resources.provider.pages.list'))
            ->emptyStateHeading(__('phpinnacle-cerber::resources.provider.empty.heading'))
            ->emptyStateDescription(__('phpinnacle-cerber::resources.provider.empty.description'))
            ->emptyStateIcon(ProviderResource::getNavigationIcon())
            ->defaultSort('sort')
            ->reorderable('sort')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('accounts'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('phpinnacle-cerber::resources.provider.fields.name'))
                    ->description(fn (Provider $record) => $record->redirectUrl())
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('phpinnacle-cerber::resources.provider.fields.type'))
                    ->getStateUsing(fn (ProviderRegistry $registry, Provider $record) => $registry->get($record->type))
                    ->badge()
                    ->sortable(),
                TextColumn::make('accounts_count')
                    ->label(__('phpinnacle-cerber::resources.provider.fields.accounts'))
                    ->counts('accounts')
                    ->badge()
                    ->alignCenter()
                    ->sortable(),
                ActiveColumn::make()
                    ->action(fn (Provider $record) => $record->toggleActive()),
                CreatedColumn::make(),
                UpdatedColumn::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('phpinnacle-cerber::resources.provider.actions.create')),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('phpinnacle-cerber::resources.provider.actions.update'))
                    ->iconButton(),
                DeleteAction::make()
                    ->label(__('phpinnacle-cerber::resources.provider.actions.delete'))
                    ->iconButton(),
            ]);
    }
}
