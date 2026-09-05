<?php

namespace PHPinnacle\Casus\Resources\Exceptions\Schemas;

use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Phiki\Grammar\Grammar;
use PHPinnacle\Casus\Models\Exception;
use PHPinnacle\Casus\Support\SensitiveValuePresenter;

class ExceptionInfo
{
    private const int JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components(fn (Exception $record) => [
                Section::make()
                    ->heading(__('phpinnacle-casus::resources.exception.sections.main'))
                    ->afterHeader([
                        Text::make($record->sapi)
                            ->color('primary')
                            ->badge(),
                    ])
                    ->columns()
                    ->schema([
                        TextEntry::make('message')
                            ->label(__('phpinnacle-casus::resources.exception.fields.message'))
                            ->columnSpanFull()
                            ->copyable(),
                        TextEntry::make('type')
                            ->label(__('phpinnacle-casus::resources.exception.fields.type'))
                            ->copyable(),
                        TextEntry::make('code')
                            ->label(__('phpinnacle-casus::resources.exception.fields.code'))
                            ->copyable(),
                        TextEntry::make('status')
                            ->label(__('phpinnacle-casus::resources.exception.fields.status'))
                            ->badge(),
                        TextEntry::make('occurrences')
                            ->label(__('phpinnacle-casus::resources.exception.fields.occurrences')),
                        TextEntry::make('link')
                            ->label(__('phpinnacle-casus::resources.exception.fields.link'))
                            ->getStateUsing($record->link())
                            ->copyable(),
                        TextEntry::make('occurred_at')
                            ->label(__('phpinnacle-casus::resources.exception.fields.occurred_at'))
                            ->dateTime()
                            ->copyable(),
                        TextEntry::make('note')
                            ->label(__('phpinnacle-casus::resources.exception.fields.note'))
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ]),
                Section::make()
                    ->heading(__('phpinnacle-casus::resources.exception.sections.context'))
                    ->visible(fn (?array $state) => $state !== null && $state !== [])
                    ->schema([
                        CodeEntry::make('context')
                            ->hiddenLabel()
                            ->grammar(Grammar::Json)
                            ->jsonFlags(self::JSON_FLAGS),
                    ]),
                Section::make()
                    ->heading(__('phpinnacle-casus::resources.exception.sections.http'))
                    ->afterHeader([
                        Text::make($record->method?->getLabel() ?? 'CLI')
                            ->color($record->method?->getColor() ?? 'gray')
                            ->badge(),
                    ])
                    ->visible(fn () => $record->method !== null || $record->path !== null)
                    ->columns()
                    ->schema([
                        TextEntry::make('path')
                            ->label(__('phpinnacle-casus::resources.exception.fields.path'))
                            ->copyable(),
                        TextEntry::make('ip')
                            ->label(__('phpinnacle-casus::resources.exception.fields.ip'))
                            ->copyable(),
                        CodeEntry::make('query')
                            ->label(__('phpinnacle-casus::resources.exception.fields.query'))
                            ->columnSpanFull()
                            ->grammar(Grammar::Json)
                            ->jsonFlags(self::JSON_FLAGS),
                        CodeEntry::make('body')
                            ->label(__('phpinnacle-casus::resources.exception.fields.body'))
                            ->columnSpanFull()
                            ->grammar($record->bodyGrammar())
                            ->jsonFlags(self::JSON_FLAGS),
                        KeyValueEntry::make('headers')
                            ->label(__('phpinnacle-casus::resources.exception.sections.headers'))
                            ->keyLabel(__('phpinnacle-casus::resources.exception.fields.header_key'))
                            ->valueLabel(__('phpinnacle-casus::resources.exception.fields.header_value'))
                            ->state(fn ($record) => app(SensitiveValuePresenter::class)->present(
                                $record->headers ?? [],
                            )),
                        KeyValueEntry::make('cookies')
                            ->label(__('phpinnacle-casus::resources.exception.sections.cookies'))
                            ->keyLabel(__('phpinnacle-casus::resources.exception.fields.cookie_key'))
                            ->valueLabel(__('phpinnacle-casus::resources.exception.fields.cookie_value'))
                            ->state(fn ($record) => app(SensitiveValuePresenter::class)->present(
                                $record->cookies ?? [],
                            )),
                    ]),
                Section::make()
                    ->heading(__('phpinnacle-casus::resources.exception.sections.trace'))
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        CodeEntry::make('trace')
                            ->hiddenLabel()
                            ->grammar(Grammar::Log),
                    ]),
            ]);
    }
}
