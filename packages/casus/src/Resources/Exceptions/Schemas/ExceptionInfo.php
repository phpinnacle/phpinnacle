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

class ExceptionInfo
{
    private const int JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;

    private const array SENSITIVE_KEYS = [
        'authorization',
        'cookie',
        'x-csrf-token',
        'x-xsrf-token',
        'xsrf-token',
        '_token',
        'csrf_token',
        'session',
        'laravel_session',
        '_session',
        'remember_web',
        'remember_token',
        'bearer',
    ];

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
                        TextEntry::make('link')
                            ->label(__('phpinnacle-casus::resources.exception.fields.link'))
                            ->getStateUsing($record->link())
                            ->copyable(),
                        TextEntry::make('occurred_at')
                            ->label(__('phpinnacle-casus::resources.exception.fields.occurred_at'))
                            ->dateTime()
                            ->copyable(),
                    ]),
                Section::make()
                    ->heading(__('phpinnacle-casus::resources.exception.sections.context'))
                    ->visible(fn (mixed $state) => !empty($state))
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
                            ->state(fn ($record) => self::maskSensitive($record->headers ?? [])),
                        KeyValueEntry::make('cookies')
                            ->label(__('phpinnacle-casus::resources.exception.sections.cookies'))
                            ->keyLabel(__('phpinnacle-casus::resources.exception.fields.cookie_key'))
                            ->valueLabel(__('phpinnacle-casus::resources.exception.fields.cookie_value'))
                            ->state(fn ($record) => self::maskSensitive($record->cookies ?? [])),
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

    private static function flatten(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_map(fn ($item) => is_scalar($item)
                    ? (string) $item
                    : json_encode($item, JSON_UNESCAPED_UNICODE), $value));
            } elseif (!is_scalar($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            $result[$key] = (string) $value;
        }

        return $result;
    }

    private static function maskSensitive(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $result = [];

        foreach ($data as $key => $value) {
            $lower = strtolower((string) $key);

            $sensitive = false;
            foreach (self::SENSITIVE_KEYS as $pattern) {
                if (str_contains($lower, $pattern)) {
                    $sensitive = true;

                    break;
                }
            }

            $result[$key] = $sensitive ? '[hidden]' : $value;
        }

        return self::flatten($result);
    }
}
