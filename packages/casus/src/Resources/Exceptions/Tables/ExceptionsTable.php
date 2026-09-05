<?php

namespace PHPinnacle\Casus\Resources\Exceptions\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use PHPinnacle\Casus\Enums\ExceptionStatus;
use PHPinnacle\Casus\Enums\HttpMethod;
use PHPinnacle\Casus\Models\Exception;
use PHPinnacle\Casus\Resources\Exceptions\ExceptionResource;
use PHPinnacle\Tempo\Filters\DateRangeFilter;

class ExceptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading(__('phpinnacle-casus::resources.exception.pages.list'))
            ->emptyStateHeading(__('phpinnacle-casus::resources.exception.empty.heading'))
            ->emptyStateDescription(__('phpinnacle-casus::resources.exception.empty.description'))
            ->emptyStateIcon(ExceptionResource::getNavigationIcon())
            ->defaultSort('occurred_at', 'desc')
            ->columns([
                TextColumn::make('message')
                    ->label(__('phpinnacle-casus::resources.exception.fields.message'))
                    ->limit()
                    ->tooltip(fn (Exception $record) => $record->message)
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('phpinnacle-casus::resources.exception.fields.type'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('code')
                    ->label(__('phpinnacle-casus::resources.exception.fields.code'))
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('phpinnacle-casus::resources.exception.fields.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('occurrences')
                    ->label(__('phpinnacle-casus::resources.exception.fields.occurrences'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('method')
                    ->label(__('phpinnacle-casus::resources.exception.fields.method'))
                    ->badge(),
                TextColumn::make('path')
                    ->label(__('phpinnacle-casus::resources.exception.fields.path'))
                    ->searchable(),
                TextColumn::make('ip')
                    ->label(__('phpinnacle-casus::resources.exception.fields.ip'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('file')
                    ->label(__('phpinnacle-casus::resources.exception.fields.file'))
                    ->limit(50)
                    ->tooltip(fn (Exception $record) => $record->file)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('line')
                    ->label(__('phpinnacle-casus::resources.exception.fields.line'))
                    ->toggleable(),
                TextColumn::make('occurred_at')
                    ->label(__('phpinnacle-casus::resources.exception.fields.occurred_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('trace')
                    ->searchable()
                    ->hidden(),
            ])
            ->filters([
                DateRangeFilter::make('occurred_at')
                    ->label(__('phpinnacle-casus::resources.exception.filters.occurred_at')),
                SelectFilter::make('type')
                    ->label(__('phpinnacle-casus::resources.exception.fields.type'))
                    ->options(
                        fn () => Exception::query()
                            ->distinct()
                            ->orderBy('type')
                            ->pluck('type', 'type')
                            ->toArray(),
                    )
                    ->searchable(),
                SelectFilter::make('method')
                    ->label(__('phpinnacle-casus::resources.exception.fields.method'))
                    ->options(HttpMethod::class),
                SelectFilter::make('status')
                    ->label(__('phpinnacle-casus::resources.exception.fields.status'))
                    ->options(ExceptionStatus::class),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
