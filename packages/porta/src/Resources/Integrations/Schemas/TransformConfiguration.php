<?php

namespace PHPinnacle\Porta\Resources\Integrations\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Group;
use Illuminate\Support\Facades\Blade;
use PHPinnacle\Intl\Forms\LocalePicker;
use PHPinnacle\Porta\Enums\TransformType;

/** @phpstan-import-type TransformConfig from TransformType */
readonly class TransformConfiguration
{
    public function __construct(
        private TransformType $type,
    ) {}

    /**
     * @return list<\Filament\Schemas\Components\Component>
     */
    public function form(): array
    {
        return match ($this->type) {
            TransformType::MAP, TransformType::RENAME, TransformType::INSERT => [
                KeyValue::make('fields')
                    ->reorderable()
                    ->required(),
            ],
            TransformType::MOVE => [
                TextInput::make('dest')
                    ->required(),
            ],
            TransformType::CAST => [
                Select::make('type')
                    ->required()
                    ->default('string')
                    ->options([
                        'boolean' => 'boolean',
                        'integer' => 'integer',
                        'double' => 'double',
                        'string' => 'string',
                    ]),
            ],
            TransformType::TRIM => [
                TextInput::make('chars'),
            ],
            TransformType::DATE => [
                Group::make()
                    ->columns()
                    ->schema([
                        TextInput::make('input'),
                        TextInput::make('output'),
                        TextInput::make('modify'),
                        Select::make('behavior')
                            ->default('skip')
                            ->options([
                                'skip' => 'skip',
                                'null' => 'null',
                                'now' => 'now',
                            ])
                            ->selectablePlaceholder(false),
                        LocalePicker::make('locale')
                            ->selectablePlaceholder(false),
                        ToggleButtons::make('future')
                            ->grouped()
                            ->boolean(),
                    ]),
            ],
            TransformType::DROP => [],
        };
    }

    /**
     * @param TransformConfig $config
     */
    public function preview(array $config): string
    {
        return match ($this->type) {
            TransformType::MAP, TransformType::INSERT => Blade::render(
                '@foreach ($fields as $k => $v)<div><x-filament::badge color="success">{{ $k }} => {{ $v }}</x-filament::badge></div>@endforeach',
                [
                    'fields' => $config['fields'] ?? [],
                ],
            ),
            TransformType::RENAME => Blade::render(
                '@foreach ($fields as $k => $v)<div><x-filament::badge color="warning">{{ $k }} => {{ $v }}</x-filament::badge></div>@endforeach',
                [
                    'fields' => $config['fields'] ?? [],
                ],
            ),
            TransformType::MOVE => Blade::render(
                '<x-filament::badge>{{ $destination }}</x-filament::badge>',
                ['destination' => $config['dest'] ?? '$'],
            ),
            TransformType::CAST => Blade::render(
                '<x-filament::badge color="warning">{{ $type }}</x-filament::badge>',
                ['type' => $config['type'] ?? 'string'],
            ),
            TransformType::DROP
                => '<div class="fi-ta-placeholder text-sm leading-6 text-gray-400 dark:text-gray-500">-</div>',
            TransformType::TRIM => filled($config['chars'] ?? null)
                ? Blade::render('@foreach ($chars as $char)<x-filament::badge>{{ $char }}</x-filament::badge>@endforeach', [
                    'chars' => str_split($config['chars']),
                ])
                : '<div class="fi-ta-placeholder text-sm leading-6 text-gray-400 dark:text-gray-500">-</div>',
            TransformType::DATE => '<span class="fi-ta-placeholder text-sm leading-6 text-gray-400 dark:text-gray-500">'
                . e(filled($config['input'] ?? null) ? $config['input'] : 'Any')
                . ' => '
                . e(filled($config['output'] ?? null) ? $config['output'] : 'd.m.Y')
                . '</span>',
        };
    }
}
