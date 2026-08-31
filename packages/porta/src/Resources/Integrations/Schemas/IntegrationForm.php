<?php

namespace PHPinnacle\Porta\Resources\Integrations\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use PHPinnacle\Common\Forms\ActiveSelect;
use PHPinnacle\Porta\Enums\IntegrationAuth;
use PHPinnacle\Porta\Enums\IntegrationFormat;
use PHPinnacle\Porta\Enums\IntegrationResponse;
use PHPinnacle\Porta\Enums\TransformType;
use PHPinnacle\Porta\Models\Integration;
use PHPinnacle\Porta\Services\WebhookRegistry;

class IntegrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->heading(__('phpinnacle-porta::resources.integration.sections.general.heading'))
                    ->columns(3)
                    ->schema([
                        TextInput::make('title')
                            ->label(__('phpinnacle-porta::resources.integration.fields.title'))
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label(__('phpinnacle-porta::resources.integration.fields.type'))
                            ->options(fn (WebhookRegistry $registry) => $registry->options())
                            ->required(),
                        ActiveSelect::make(),
                        Select::make('format')
                            ->label(__('phpinnacle-porta::resources.integration.fields.format'))
                            ->options(IntegrationFormat::class)
                            ->default(IntegrationFormat::JSON)
                            ->enum(IntegrationFormat::class)
                            ->required()
                            ->selectablePlaceholder(false),
                        Select::make('response_kind')
                            ->label(__('phpinnacle-porta::resources.integration.fields.response_kind'))
                            ->options(IntegrationResponse::class)
                            ->default(IntegrationResponse::Empty)
                            ->enum(IntegrationResponse::class)
                            ->required()
                            ->selectablePlaceholder(false),
                        TextInput::make('response_code')
                            ->label(__('phpinnacle-porta::resources.integration.fields.response_code'))
                            ->integer()
                            ->required()
                            ->default(200)
                            ->minValue(200)
                            ->maxValue(599),
                        TextEntry::make('url')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->visibleOn('edit')
                            ->state(fn (Integration $record) => new HtmlString(sprintf(
                                '<a href="%1$s" class="link" target="_blank" >%1$s</a>',
                                route('webhook.catch', $record->id),
                            ))),
                    ]),
                Section::make()
                    ->heading(__('phpinnacle-porta::resources.integration.sections.auth.heading'))
                    ->columns(3)
                    ->schema([
                        Select::make('auth')
                            ->label(__('phpinnacle-porta::resources.integration.fields.auth'))
                            ->options(IntegrationAuth::class)
                            ->default(IntegrationAuth::None)
                            ->enum(IntegrationAuth::class)
                            ->selectablePlaceholder(false)
                            ->required()
                            ->live(),
                        TextInput::make('auth_key')
                            ->label(__('phpinnacle-porta::resources.integration.fields.auth_key'))
                            ->disabled(
                                fn (Get $get) => $get->enum('auth', IntegrationAuth::class) === IntegrationAuth::None,
                            )
                            ->nullable(),
                        TextInput::make('auth_secret')
                            ->label(__('phpinnacle-porta::resources.integration.fields.auth_secret'))
                            ->disabled(
                                fn (Get $get) => $get->enum('auth', IntegrationAuth::class) === IntegrationAuth::None,
                            )
                            ->password()
                            ->revealable()
                            ->suffixActions([
                                Action::make('generate')
                                    ->icon('heroicon-o-arrow-path')
                                    ->color('gray')
                                    ->requiresConfirmation()
                                    ->action(fn (TextInput $component) => $component->state(Str::random(12))),
                            ])
                            ->nullable(),
                    ]),
                Section::make()
                    ->heading(__('phpinnacle-porta::resources.integration.sections.transforms.heading'))
                    ->extraAttributes(['class' => 'fi-section-attributes'])
                    ->schema([
                        Repeater::make('transforms')
                            ->columnSpanFull()
                            ->hiddenLabel()
                            ->cloneable()
                            ->defaultItems(0)
                            ->compact()
                            ->table([
                                Repeater\TableColumn::make(__(
                                    'phpinnacle-porta::resources.integration.fields.transforms_type',
                                ))
                                    ->markAsRequired()
                                    ->width('10%'),
                                Repeater\TableColumn::make(__(
                                    'phpinnacle-porta::resources.integration.fields.transforms_path',
                                ))
                                    ->markAsRequired()
                                    ->width('30%'),
                                Repeater\TableColumn::make(__(
                                    'phpinnacle-porta::resources.integration.fields.transforms_config',
                                ))
                                    ->width('60%'),
                            ])
                            ->schema([
                                Select::make('type')
                                    ->options(TransformType::class)
                                    ->default(TransformType::MAP)
                                    ->enum(TransformType::class)
                                    ->required()
                                    ->selectablePlaceholder(false),
                                TextInput::make('path')
                                    ->default('$'),
                                TextEntry::make('_config')
                                    ->hiddenLabel()
                                    ->extraAttributes(['class' => 'flex relative p-2'], merge: true)
                                    ->state(function (Get $get) {
                                        $type = $get->enum('type', TransformType::class);

                                        return new HtmlString($type->preview($get->array('config')));
                                    }),
                                Hidden::make('config')
                                    ->default([])
                                    ->live(),
                            ])
                            ->extraItemActions([
                                Action::make('config')
                                    ->icon('heroicon-o-cog-6-tooth')
                                    ->color(function (array $arguments, Repeater $component) {
                                        /** @var MessageBag $errors */
                                        $errors = $component->getLivewire()->getErrorBag();

                                        return $errors->has(sprintf('data.attributes.%s.config', $arguments['item']))
                                            ? 'danger'
                                            : null;
                                    })
                                    ->schema(function (array $arguments, Repeater $component) {
                                        $id = $arguments['item'];
                                        $state = $component->getRawItemState($id);

                                        return TransformType::resolve($state['type'])->form();
                                    })
                                    ->fillForm(function (array $arguments, Repeater $component) {
                                        $id = $arguments['item'];
                                        $state = $component->getRawItemState($id);

                                        return $state['config'] ?? [];
                                    })
                                    ->action(function (array $arguments, array $data, Repeater $component) {
                                        $id = $arguments['item'];
                                        $state = $component->getState();

                                        $state[$id]['config'] = $data;

                                        $component->state($state);
                                        $component->callAfterStateUpdated();
                                    }),
                            ]),
                    ]),
            ]);
    }
}
