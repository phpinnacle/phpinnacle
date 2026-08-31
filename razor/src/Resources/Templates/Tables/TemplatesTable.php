<?php

namespace PHPinnacle\Razor\Resources\Templates\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Schemas\Components\Group;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use PHPinnacle\Razor\Models\Template;
use PHPinnacle\Razor\Resources\Templates\Schemas\TemplateForm;
use PHPinnacle\Razor\Resources\Templates\TemplateResource;
use PHPinnacle\Razor\Services\Registry;
use PHPinnacle\Tempo\Filters\DateRangeFilter;

class TemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading(__('phpinnacle-razor::resources.template.pages.list'))
            ->emptyStateHeading(__('phpinnacle-razor::resources.template.empty.heading'))
            ->emptyStateDescription(__('phpinnacle-razor::resources.template.empty.description'))
            ->emptyStateIcon(TemplateResource::getNavigationIcon())
            ->columns([
                TextColumn::make('name')
                    ->label(__('phpinnacle-razor::resources.template.fields.name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('section')
                    ->label(__('phpinnacle-razor::resources.template.fields.section'))
                    ->badge()
                    ->sortable()
                    ->searchable(),
                IconColumn::make('is_default')
                    ->label(__('phpinnacle-razor::resources.template.fields.is_default'))
                    ->boolean()
                    ->action(fn (Template $record) => $record->toggleDefault())
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('phpinnacle-razor::resources.template.fields.is_active'))
                    ->boolean()
                    ->action(fn (Template $record) => $record->toggleActive())
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('phpinnacle-razor::resources.template.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('phpinnacle-razor::resources.template.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ReplicateAction::make()
                    ->label(__('phpinnacle-razor::resources.template.actions.replicate'))
                    ->mutateRecordDataUsing(function (array $data) {
                        unset(
                            $data['id'],
                            $data['parent_id'],
                            $data['created_by'],
                            $data['created_at'],
                            $data['updated_at'],
                        );

                        $data['version'] = 1;
                        $data['is_default'] = false;

                        return $data;
                    })
                    ->iconButton(),
                DeleteAction::make()
                    ->label(__('phpinnacle-razor::resources.template.actions.delete'))
                    ->iconButton(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label(__('phpinnacle-razor::resources.template.actions.delete')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('phpinnacle-razor::resources.template.actions.create'))
                    ->schema([
                        Group::make()
                            ->columns()
                            ->schema(TemplateForm::common()),
                    ]),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('phpinnacle-razor::resources.template.filters.active'))
                    ->columnSpanFull(),
                SelectFilter::make('section')
                    ->label(__('phpinnacle-razor::resources.template.filters.section'))
                    ->options(fn (Registry $registry) => Arr::pluck($registry->all(), 'label', 'key'))
                    ->columnSpanFull(),
                DateRangeFilter::make('created_at')
                    ->label(__('phpinnacle-razor::resources.template.filters.created_at'))
                    ->columnSpanFull(),
            ]);
    }
}
