<?php

namespace PHPinnacle\Cerber\Resources\Roles\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use PHPinnacle\Cerber\Models\Role;
use PHPinnacle\Cerber\Resources\Roles\RoleResource;
use PHPinnacle\Common\Filters\ActiveFilter;
use PHPinnacle\Common\Filters\HasFilter;
use PHPinnacle\Common\Tables\ActiveColumn;
use PHPinnacle\Common\Tables\CreatedColumn;
use PHPinnacle\Common\Tables\UpdatedColumn;
use PHPinnacle\Tempo\Filters\DateRangeFilter;

class RoleTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading(__('phpinnacle-cerber::resources.role.pages.list'))
            ->emptyStateHeading(__('phpinnacle-cerber::resources.role.empty.heading'))
            ->emptyStateDescription(__('phpinnacle-cerber::resources.role.empty.description'))
            ->emptyStateIcon(RoleResource::getNavigationIcon())
            ->filtersFormColumns(2)
            ->filtersFormWidth(Width::Medium)
            ->columns([
                TextColumn::make('name')
                    ->label(__('phpinnacle-cerber::resources.role.fields.name'))
                    ->description(fn (Role $record) => Str::limit(strip_tags($record->description), 40))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('permissions_count')
                    ->label(__('phpinnacle-cerber::resources.role.fields.permissions'))
                    ->counts('permissions')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('users_count')
                    ->label(__('phpinnacle-cerber::resources.role.fields.users'))
                    ->counts('users')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_system')
                    ->label(__('phpinnacle-cerber::resources.role.fields.is_system'))
                    ->boolean()
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ActiveColumn::make()
                    ->action(fn (Role $record) => $record->toggleActive()),
                CreatedColumn::make(),
                UpdatedColumn::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('phpinnacle-cerber::resources.role.actions.create')),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label(__('phpinnacle-cerber::resources.role.actions.delete'))
                    ->iconButton()
                    ->disabled(fn (Role $record) => $record->is_system),
            ])
            ->filters([
                ActiveFilter::make()
                    ->columnSpanFull(),
                HasFilter::make('users')
                    ->label(__('phpinnacle-cerber::resources.role.filters.users')),
                HasFilter::make('permissions')
                    ->label(__('phpinnacle-cerber::resources.role.filters.permissions')),
                DateRangeFilter::createdAt(),
                DateRangeFilter::updatedAt(),
            ]);
    }
}
