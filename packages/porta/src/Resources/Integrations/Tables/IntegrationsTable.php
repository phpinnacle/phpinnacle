<?php

namespace PHPinnacle\Porta\Resources\Integrations\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use PHPinnacle\Common\Filters\ActiveFilter;
use PHPinnacle\Common\Tables\ActiveColumn;
use PHPinnacle\Common\Tables\CreatedColumn;
use PHPinnacle\Common\Tables\UpdatedColumn;
use PHPinnacle\Porta\Enums\IntegrationAuth;
use PHPinnacle\Porta\Enums\IntegrationFormat;
use PHPinnacle\Porta\Enums\IntegrationResponse;
use PHPinnacle\Porta\Models\Integration;
use PHPinnacle\Porta\Resources\Integrations\IntegrationResource;
use PHPinnacle\Porta\Services\WebhookRegistry;
use PHPinnacle\Tempo\Filters\DateRangeFilter;

class IntegrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading(__('phpinnacle-porta::resources.integration.pages.list'))
            ->emptyStateHeading(__('phpinnacle-porta::resources.integration.empty.heading'))
            ->emptyStateDescription(__('phpinnacle-porta::resources.integration.empty.description'))
            ->emptyStateIcon(IntegrationResource::getNavigationIcon())
            ->columns([
                TextColumn::make('title')
                    ->label(__('phpinnacle-porta::resources.integration.fields.title'))
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('phpinnacle-porta::resources.integration.fields.type'))
                    ->getStateUsing(fn (
                        Integration $record,
                        WebhookRegistry $registry,
                    ) => $registry->get($record->type))
                    ->badge(),
                TextColumn::make('format')
                    ->label(__('phpinnacle-porta::resources.integration.fields.format'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('auth')
                    ->label(__('phpinnacle-porta::resources.integration.fields.auth'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('response_kind')
                    ->label(__('phpinnacle-porta::resources.integration.fields.response_kind'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('response_code')
                    ->label(__('phpinnacle-porta::resources.integration.fields.response_code'))
                    ->toggleable(),
                ActiveColumn::make()
                    ->action(fn (Integration $record) => $record->toggleActive()),
                CreatedColumn::make(),
                UpdatedColumn::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('phpinnacle-porta::resources.integration.actions.create')),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label(__('phpinnacle-porta::resources.integration.actions.delete')),
            ])
            ->recordActions([
                ReplicateAction::make()
                    ->label(__('phpinnacle-porta::resources.integration.actions.clone'))
                    ->iconButton(),
                DeleteAction::make()
                    ->label(__('phpinnacle-porta::resources.integration.actions.delete'))
                    ->iconButton(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('phpinnacle-porta::resources.integration.filters.type'))
                    ->options(fn (WebhookRegistry $registry) => $registry->options()),
                SelectFilter::make('format')
                    ->label(__('phpinnacle-porta::resources.integration.filters.format'))
                    ->options(IntegrationFormat::class),
                SelectFilter::make('auth')
                    ->label(__('phpinnacle-porta::resources.integration.filters.auth'))
                    ->options(IntegrationAuth::class),
                SelectFilter::make('response_kind')
                    ->label(__('phpinnacle-porta::resources.integration.filters.response_kind'))
                    ->options(IntegrationResponse::class),
                ActiveFilter::make(),
                DateRangeFilter::createdAt(),
                DateRangeFilter::updatedAt(),
            ]);
    }
}
