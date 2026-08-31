<?php

namespace PHPinnacle\Cerber\Resources\Users\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use PHPinnacle\Cerber\Enums\UserStatus;
use PHPinnacle\Cerber\Models\User;
use PHPinnacle\Cerber\Resources\Users\Actions\ImpersonateAction;
use PHPinnacle\Cerber\Resources\Users\Actions\StatusAction;
use PHPinnacle\Common\Tables\CreatedColumn;
use PHPinnacle\Common\Tables\UpdatedColumn;
use PHPinnacle\Tempo\Filters\DateRangeFilter;

class UserTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading(__('phpinnacle-cerber::resources.user.pages.list'))
            ->columns([
                ImageColumn::make('avatar')
                    ->label(__('phpinnacle-cerber::resources.user.fields.avatar'))
                    ->getStateUsing(fn (User $record) => $record->getFilamentAvatarUrl())
                    ->circular()
                    ->alignCenter()
                    ->width('40px'),
                TextColumn::make('name')
                    ->label(__('phpinnacle-cerber::resources.user.fields.name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('phpinnacle-cerber::resources.user.fields.email'))
                    ->icon(fn (User $record) => $record->email_verified_at
                        ? 'phosphor-check-circle'
                        : 'phosphor-x-circle')
                    ->iconColor(fn (User $record) => $record->email_verified_at ? 'success' : 'danger')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('roles')
                    ->label(__('phpinnacle-cerber::resources.user.fields.roles'))
                    ->badge()
                    ->limitList(2)
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('phpinnacle-cerber::resources.user.fields.status'))
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                CreatedColumn::make(),
                UpdatedColumn::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('phpinnacle-cerber::resources.user.actions.create')),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label(__('phpinnacle-cerber::resources.user.actions.delete')),
            ])
            ->recordActions([
                ImpersonateAction::make()
                    ->iconButton(),
                StatusAction::make()
                    ->disabled(fn (User $record) => $record->id === auth()->id())
                    ->iconButton(),
                DeleteAction::make()
                    ->label(__('phpinnacle-cerber::resources.user.actions.delete'))
                    ->disabled(fn (User $record) => $record->id === auth()->id())
                    ->iconButton(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('phpinnacle-cerber::resources.user.fields.status'))
                    ->options(UserStatus::class),
                SelectFilter::make('roles')
                    ->label(__('phpinnacle-cerber::resources.user.fields.roles'))
                    ->relationship('roles', 'name'),
                TernaryFilter::make('email_verified_at')
                    ->label(__('phpinnacle-cerber::resources.user.fields.email_verified_at'))
                    ->nullable(),
                DateRangeFilter::createdAt(),
                DateRangeFilter::updatedAt(),
            ])
            ->groups([
                Group::make('status')
                    ->label(__('phpinnacle-cerber::resources.user.fields.status')),
            ]);
    }
}
