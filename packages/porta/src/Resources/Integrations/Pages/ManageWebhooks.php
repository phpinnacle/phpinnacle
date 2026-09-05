<?php

namespace PHPinnacle\Porta\Resources\Integrations\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;
use PHPinnacle\Common\Tables\CreatedColumn;
use PHPinnacle\Common\Tables\UpdatedColumn;
use PHPinnacle\Porta\Enums\WebhookStatus;
use PHPinnacle\Porta\Models\Integration;
use PHPinnacle\Porta\Models\Webhook;
use PHPinnacle\Porta\Resources\Integrations\Actions\ExampleAction;
use PHPinnacle\Porta\Resources\Integrations\Actions\RetryAction;
use PHPinnacle\Porta\Resources\Integrations\IntegrationResource;
use PHPinnacle\Tempo\Filters\DateRangeFilter;

/**
 * @property Integration $record
 */
class ManageWebhooks extends ManageRelatedRecords
{
    protected static string $resource = IntegrationResource::class;

    protected static string $relationship = 'webhooks';

    public static function getNavigationIcon(): ?string
    {
        return config('phpinnacle-porta.navigation.webhook.icon');
    }

    public static function getNavigationLabel(): string
    {
        return __('phpinnacle-porta::resources.integration.pages.webhooks');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('origin')
                            ->label(__('phpinnacle-porta::resources.webhook.fields.origin'))
                            ->prefixIcon('phosphor-browser')
                            ->default(config('app.url'))
                            ->url(),
                        Textarea::make('payload')
                            ->label(__('phpinnacle-porta::resources.webhook.fields.payload'))
                            ->json()
                            ->rows(20)
                            ->hintActions([
                                ExampleAction::make(),
                            ]),
                        KeyValue::make('headers')
                            ->label(__('phpinnacle-porta::resources.webhook.fields.headers')),
                    ]),
            ]);
    }

    public function getTitle(): string|Htmlable
    {
        return __('phpinnacle-porta::resources.webhook.pages.list');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components(fn (Webhook $record) => [
                Group::make()
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        TextEntry::make('origin')
                            ->label(__('phpinnacle-porta::resources.webhook.fields.origin'))
                            ->url(fn (string $state) => $state)
                            ->openUrlInNewTab(),
                        TextEntry::make('status')
                            ->label(__('phpinnacle-porta::resources.webhook.fields.status'))
                            ->badge()
                            ->copyable(),
                        TextEntry::make('payload')
                            ->label(__('phpinnacle-porta::resources.webhook.fields.payload'))
                            ->extraEntryWrapperAttributes(['class' => 'break-all'], true)
                            ->copyable()
                            ->columnSpanFull(),
                        KeyValueEntry::make('headers')
                            ->label(__('phpinnacle-porta::resources.webhook.fields.headers'))
                            ->visible(fn (?array $state) => $state !== null && $state !== [])
                            ->columnSpanFull(),
                    ]),
                Group::make()
                    ->columnSpanFull()
                    ->columns()
                    ->statePath('error')
                    ->visible(
                        Gate::allows('viewError', Integration::class)
                        && $record->error !== null
                        && $record->error !== [],
                    )
                    ->schema([
                        TextEntry::make('message')
                            ->label(__('phpinnacle-porta::resources.webhook.fields.error_message'))
                            ->copyable(),
                        TextEntry::make('type')
                            ->label(__('phpinnacle-porta::resources.webhook.fields.error_type'))
                            ->color('danger')
                            ->badge()
                            ->copyable(),
                        TextEntry::make('file')
                            ->label(__('phpinnacle-porta::resources.webhook.fields.error_file'))
                            ->getStateUsing(implode(':', [
                                $record->error['file'] ?? null,
                                $record->error['line'] ?? null,
                            ]))
                            ->columnSpanFull(),
                        KeyValueEntry::make('errors')
                            ->label(__('phpinnacle-porta::resources.webhook.fields.error_validation'))
                            ->getStateUsing(array_map(
                                fn (mixed $error) => is_array($error) ? implode(PHP_EOL, $error) : $error,
                                $record->error['errors'] ?? [],
                            ))
                            ->visible(fn (?array $state) => $state !== null && $state !== [])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('phpinnacle-porta::resources.webhook.pages.list'))
            ->emptyStateIcon(self::getNavigationIcon())
            ->emptyStateHeading(__('phpinnacle-porta::resources.webhook.empty.heading'))
            ->emptyStateDescription(__('phpinnacle-porta::resources.webhook.empty.description'))
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label(__('phpinnacle-porta::resources.webhook.fields.id'))
                    ->searchable(),
                TextColumn::make('origin')
                    ->label(__('phpinnacle-porta::resources.webhook.fields.origin'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('phpinnacle-porta::resources.webhook.fields.status'))
                    ->badge(),
                TextColumn::make('processed_at')
                    ->label(__('phpinnacle-porta::resources.webhook.fields.processed_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                CreatedColumn::make(),
                UpdatedColumn::make(),
            ])
            ->recordActions([
                RetryAction::make(),
                ViewAction::make()
                    ->label(__('phpinnacle-porta::resources.webhook.actions.view'))
                    ->slideOver()
                    ->modalWidth(Width::ScreenExtraLarge),
                DeleteAction::make()
                    ->label(__('phpinnacle-porta::resources.webhook.actions.delete')),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label(__('phpinnacle-porta::resources.webhook.actions.delete')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('phpinnacle-porta::resources.webhook.actions.create')),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('phpinnacle-porta::resources.webhook.filters.status'))
                    ->options(WebhookStatus::class),
                DateRangeFilter::make('processed_at')
                    ->label(__('phpinnacle-porta::resources.webhook.filters.processed_at')),
                DateRangeFilter::createdAt(),
                DateRangeFilter::updatedAt(),
            ]);
    }
}
