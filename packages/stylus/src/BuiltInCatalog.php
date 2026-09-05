<?php

namespace PHPinnacle\Stylus;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Support\Icons\Heroicon;
use PHPinnacle\Stylus\Enums\FilterOutput;

final class BuiltInCatalog
{
    /** @return list<Condition> */
    public static function conditions(): array
    {
        return [
            Condition::comparison('equals', '==')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.equals.label'))
                ->icon(Heroicon::Scale)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.equals.description'))
                ->color('gray')
                ->types('text', 'number', 'integer', 'boolean'),
            Condition::test('defined')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.defined.label'))
                ->icon(Heroicon::CheckCircle)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.defined.description'))
                ->color('gray'),
            Condition::test('empty')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.empty.label'))
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.empty.description'))
                ->color('gray'),
            Condition::test('null')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.null.label'))
                ->icon(Heroicon::MinusCircle)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.null.description'))
                ->color('gray'),
            Condition::comparison('greater_than', '>')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.greater_than.label'))
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.greater_than.description'))
                ->color('warning')
                ->types('number', 'integer'),
            Condition::comparison('at_least', '>=')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.at_least.label'))
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.at_least.description'))
                ->color('warning')
                ->types('number', 'integer'),
            Condition::comparison('less_than', '<')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.less_than.label'))
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.less_than.description'))
                ->color('warning')
                ->types('number', 'integer'),
            Condition::comparison('at_most', '<=')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.at_most.label'))
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.at_most.description'))
                ->color('warning')
                ->types('number', 'integer'),
            Condition::test('even')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.even.label'))
                ->icon(Heroicon::Hashtag)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.even.description'))
                ->color('warning')
                ->types('number', 'integer'),
            Condition::test('odd')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.odd.label'))
                ->icon(Heroicon::Hashtag)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.odd.description'))
                ->color('warning')
                ->types('number', 'integer'),
            Condition::comparison('in', 'in')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.in.label'))
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.in.description'))
                ->color('primary')
                ->matchVariableTypes(false),
            Condition::test('sequence')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.sequence.label'))
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.sequence.description'))
                ->color('primary')
                ->types('collection'),
            Condition::test('mapping')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.mapping.label'))
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.conditions.mapping.description'))
                ->color('primary')
                ->types('collection'),
        ];
    }

    /** @return list<Filter> */
    public static function filters(): array
    {
        return [
            ...self::fallbackFilters(),
            ...self::textFilters(),
            ...self::dateFilters(),
            ...self::numberFilters(),
            ...self::collectionFilters(),
        ];
    }

    /** @return list<Filter> */
    private static function fallbackFilters(): array
    {
        return [
            Filter::make('default')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.default.label'))
                ->icon(Heroicon::ArrowUturnLeft)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.default.description'))
                ->color('info')
                ->types('text')
                ->schema([
                    TextInput::make('value')
                        ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.default.value'))
                        ->placeholder("'Guest'")
                        ->helperText(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.default.value_helper'))
                        ->required(),
                ]),
        ];
    }

    /** @return list<Filter> */
    private static function textFilters(): array
    {
        return [
            Filter::make('title')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.title.label'))
                ->icon(Heroicon::Language)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.title.description'))
                ->color('info')
                ->types('text'),
            Filter::make('capitalize')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.capitalize.label'))
                ->icon(Heroicon::H1)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.capitalize.description'))
                ->color('info')
                ->types('text'),
            Filter::make('upper')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.upper.label'))
                ->icon(Heroicon::BarsArrowUp)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.upper.description'))
                ->color('info')
                ->types('text')
                ->blocks(),
            Filter::make('lower')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.lower.label'))
                ->icon(Heroicon::BarsArrowDown)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.lower.description'))
                ->color('info')
                ->types('text'),
            Filter::make('trim')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.trim.label'))
                ->icon(Heroicon::ArrowsPointingIn)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.trim.description'))
                ->color('info')
                ->types('text')
                ->schema([
                    Group::make([
                        TextInput::make('character_mask')
                            ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.trim.character_mask'))
                            ->helperText(__(
                                'phpinnacle-stylus::forms.twig_editor.catalog.filters.trim.character_mask_helper',
                            ))
                            ->maxLength(100),
                        Select::make('side')
                            ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.trim.side'))
                            ->options([
                                'both' => __('phpinnacle-stylus::forms.twig_editor.catalog.filters.trim.sides.both'),
                                'left' => __('phpinnacle-stylus::forms.twig_editor.catalog.filters.trim.sides.left'),
                                'right' => __('phpinnacle-stylus::forms.twig_editor.catalog.filters.trim.sides.right'),
                            ])
                            ->default('both')
                            ->native()
                            ->required(),
                    ])->columns(2),
                ])
                ->argumentsUsing(static function (array $configuration) {
                    /** @var string|null $characterMask */
                    $characterMask = $configuration['character_mask'];

                    /** @var string $side */
                    $side = $configuration['side'];

                    if ($characterMask === null || $characterMask === '') {
                        return (
                            $side === 'both'
                                ? []
                                : ['side: ' . self::quoteTwigString($side)]
                        );
                    }

                    $arguments = [self::quoteTwigString($characterMask)];

                    if ($side !== 'both') {
                        $arguments[] = self::quoteTwigString($side);
                    }

                    return $arguments;
                }),
            Filter::make('nl2br')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.nl2br.label'))
                ->icon(Heroicon::Bars3BottomLeft)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.nl2br.description'))
                ->color('info')
                ->types('text'),
            Filter::make('striptags')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.striptags.label'))
                ->icon(Heroicon::CodeBracket)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.striptags.description'))
                ->color('info')
                ->types('text')
                ->schema([
                    TextInput::make('allowable_tags')
                        ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.striptags.allowable_tags'))
                        ->helperText(__(
                            'phpinnacle-stylus::forms.twig_editor.catalog.filters.striptags.allowable_tags_helper',
                        ))
                        ->placeholder('<br><p>')
                        ->maxLength(500)
                        ->regex('/^(?:<[A-Za-z][A-Za-z0-9:-]*>)*$/'),
                ])
                ->argumentsUsing(static function (array $configuration) {
                    /** @var string|null $allowableTags */
                    $allowableTags = $configuration['allowable_tags'];

                    if ($allowableTags === null || $allowableTags === '') {
                        return [];
                    }

                    return [self::quoteTwigString($allowableTags)];
                }),
            Filter::make('format')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.format.label'))
                ->icon(Heroicon::ChatBubbleBottomCenterText)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.format.description'))
                ->color('info')
                ->types('text')
                ->schema([
                    TagsInput::make('arguments')
                        ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.format.arguments'))
                        ->helperText(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.format.arguments_helper'))
                        ->reorderable()
                        ->trim()
                        ->nestedRecursiveRules(['string', 'max:500']),
                ]),
            Filter::make('replace')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.replace.label'))
                ->icon(Heroicon::ArrowsRightLeft)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.replace.description'))
                ->color('info')
                ->types('text')
                ->schema([
                    KeyValue::make('replacements')
                        ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.replace.replacements'))
                        ->keyLabel(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.replace.search'))
                        ->valueLabel(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.replace.replacement'))
                        ->addActionLabel(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.replace.add'))
                        ->reorderable()
                        ->required()
                        ->rules(['array', 'max:50']),
                ])
                ->argumentsUsing(static function (array $configuration) {
                    /** @var array<string, string> $replacements */
                    $replacements = $configuration['replacements'];
                    $mapping = [];

                    foreach ($replacements as $search => $replacement) {
                        $mapping[] = self::quoteTwigString($search) . ': ' . self::quoteTwigString($replacement);
                    }

                    return ['{' . implode(', ', $mapping) . '}'];
                }),
            Filter::make('escape')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.escape.label'))
                ->icon(Heroicon::ShieldCheck)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.escape.description'))
                ->color('info')
                ->types('text')
                ->blocks()
                ->schema([
                    Select::make('strategy')
                        ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.escape.strategy'))
                        ->options([
                            'html' => __('phpinnacle-stylus::forms.twig_editor.catalog.filters.escape.strategies.html'),
                            'html_attr' => __(
                                'phpinnacle-stylus::forms.twig_editor.catalog.filters.escape.strategies.html_attr',
                            ),
                            'js' => __('phpinnacle-stylus::forms.twig_editor.catalog.filters.escape.strategies.js'),
                            'css' => __('phpinnacle-stylus::forms.twig_editor.catalog.filters.escape.strategies.css'),
                            'url' => __('phpinnacle-stylus::forms.twig_editor.catalog.filters.escape.strategies.url'),
                        ])
                        ->native()
                        ->required(),
                ])
                ->argumentsUsing(static function (array $configuration) {
                    /** @var string $strategy */
                    $strategy = $configuration['strategy'];

                    return [self::quoteTwigString($strategy)];
                }),
            Filter::make('url_encode')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.url_encode.label'))
                ->icon(Heroicon::Link)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.url_encode.description'))
                ->color('info')
                ->types('text'),
            Filter::make('convert_encoding')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.convert_encoding.label'))
                ->icon(Heroicon::Language)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.convert_encoding.description'))
                ->color('info')
                ->types('text')
                ->schema([
                    Group::make([
                        TextInput::make('from')
                            ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.convert_encoding.from'))
                            ->placeholder('ISO-8859-1')
                            ->required()
                            ->maxLength(50)
                            ->regex('/^[A-Za-z0-9._:-]+$/'),
                        TextInput::make('to')
                            ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.convert_encoding.to'))
                            ->placeholder('UTF-8')
                            ->required()
                            ->maxLength(50)
                            ->regex('/^[A-Za-z0-9._:-]+$/'),
                    ])->columns(2),
                ])
                ->argumentsUsing(static function (array $configuration) {
                    /** @var string $to */
                    $to = $configuration['to'];

                    /** @var string $from */
                    $from = $configuration['from'];

                    return [self::quoteTwigString($to), self::quoteTwigString($from)];
                }),
        ];
    }

    /** @return list<Filter> */
    private static function dateFilters(): array
    {
        return [
            Filter::make('date')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.date.label'))
                ->icon(Heroicon::CalendarDays)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.date.description'))
                ->color('success')
                ->types('date')
                ->schema([
                    Select::make('format')
                        ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.date.format'))
                        ->options([
                            'd/m/Y' => __(
                                'phpinnacle-stylus::forms.twig_editor.catalog.filters.date.formats.day_month_year',
                            ),
                            'Y-m-d' => __(
                                'phpinnacle-stylus::forms.twig_editor.catalog.filters.date.formats.year_month_day',
                            ),
                            'd.m.Y' => __(
                                'phpinnacle-stylus::forms.twig_editor.catalog.filters.date.formats.day_month_year_dots',
                            ),
                        ])
                        ->native()
                        ->required(),
                ])
                ->argumentsUsing(static function (array $configuration) {
                    /** @var string $format */
                    $format = $configuration['format'];

                    return [self::quoteTwigString($format)];
                }),
            Filter::make('date_modify')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.date_modify.label'))
                ->icon(Heroicon::CalendarDays)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.date_modify.description'))
                ->color('success')
                ->types('date')
                ->schema([
                    TextInput::make('modifier')
                        ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.date_modify.modifier'))
                        ->placeholder('+1 day')
                        ->required()
                        ->maxLength(100),
                ])
                ->argumentsUsing(static function (array $configuration) {
                    /** @var string $modifier */
                    $modifier = $configuration['modifier'];

                    return [self::quoteTwigString($modifier)];
                }),
        ];
    }

    /** @return list<Filter> */
    private static function numberFilters(): array
    {
        return [
            Filter::make('abs')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.abs.label'))
                ->icon(Heroicon::Calculator)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.abs.description'))
                ->color('warning')
                ->types('number', 'integer'),
            Filter::make('round')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.round.label'))
                ->icon(Heroicon::ArrowPathRoundedSquare)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.round.description'))
                ->color('warning')
                ->types('number', 'integer')
                ->schema([
                    Group::make([
                        TextInput::make('precision')
                            ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.round.precision'))
                            ->integer()
                            ->default(0)
                            ->required(),
                        Select::make('method')
                            ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.round.method'))
                            ->options([
                                'common' => __(
                                    'phpinnacle-stylus::forms.twig_editor.catalog.filters.round.methods.common',
                                ),
                                'ceil' => __('phpinnacle-stylus::forms.twig_editor.catalog.filters.round.methods.ceil'),
                                'floor' => __(
                                    'phpinnacle-stylus::forms.twig_editor.catalog.filters.round.methods.floor',
                                ),
                            ])
                            ->default('common')
                            ->native()
                            ->required(),
                    ])->columns(2),
                ])
                ->argumentsUsing(static function (array $configuration) {
                    /** @var int|string $precision */
                    $precision = $configuration['precision'];

                    /** @var string $method */
                    $method = $configuration['method'];

                    return [(string) $precision, self::quoteTwigString($method)];
                }),
            Filter::make('number_format')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.number_format.label'))
                ->icon(Heroicon::Calculator)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.number_format.description'))
                ->color('warning')
                ->types('number', 'integer')
                ->schema([
                    Group::make([
                        TextInput::make('decimals')
                            ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.number_format.decimals'))
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        TextInput::make('decimal_point')
                            ->label(__(
                                'phpinnacle-stylus::forms.twig_editor.catalog.filters.number_format.decimal_point',
                            ))
                            ->maxLength(10)
                            ->requiredWith('thousands_separator'),
                        TextInput::make('thousands_separator')
                            ->label(__(
                                'phpinnacle-stylus::forms.twig_editor.catalog.filters.number_format.thousands_separator',
                            ))
                            ->maxLength(10),
                    ])->columns(3),
                ])
                ->argumentsUsing(static function (array $configuration) {
                    /** @var int|string $decimals */
                    $decimals = $configuration['decimals'];

                    /** @var string|null $decimalPoint */
                    $decimalPoint = $configuration['decimal_point'];

                    /** @var string|null $thousandsSeparator */
                    $thousandsSeparator = $configuration['thousands_separator'];
                    $arguments = [(string) $decimals];

                    if ($decimalPoint === null || $decimalPoint === '') {
                        return $arguments;
                    }

                    $arguments[] = self::quoteTwigString($decimalPoint);

                    if ($thousandsSeparator !== null && $thousandsSeparator !== '') {
                        $arguments[] = self::quoteTwigString($thousandsSeparator);
                    }

                    return $arguments;
                }),
        ];
    }

    /** @return list<Filter> */
    private static function collectionFilters(): array
    {
        return [
            Filter::make('sort')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.sort.label'))
                ->icon(Heroicon::BarsArrowDown)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.sort.description'))
                ->color('primary')
                ->types('collection'),
            Filter::make('first')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.first.label'))
                ->icon(Heroicon::Backward)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.first.description'))
                ->color('primary')
                ->types('collection')
                ->output(FilterOutput::CollectionItem),
            Filter::make('last')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.last.label'))
                ->icon(Heroicon::Forward)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.last.description'))
                ->color('primary')
                ->types('collection')
                ->output(FilterOutput::CollectionItem),
            Filter::make('keys')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.keys.label'))
                ->icon(Heroicon::Key)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.keys.description'))
                ->color('primary')
                ->types('collection'),
            Filter::make('slice')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.slice.label'))
                ->icon(Heroicon::Scissors)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.slice.description'))
                ->color('gray')
                ->types('text', 'collection')
                ->schema([
                    Group::make([
                        TextInput::make('offset')
                            ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.slice.offset'))
                            ->integer()
                            ->required(),
                        TextInput::make('length')
                            ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.slice.length'))
                            ->integer(),
                    ])->columns(2),
                    Toggle::make('preserve_keys')
                        ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.slice.preserve_keys'))
                        ->helperText(__(
                            'phpinnacle-stylus::forms.twig_editor.catalog.filters.slice.preserve_keys_helper',
                        ))
                        ->default(false),
                ])
                ->argumentsUsing(static function (array $configuration) {
                    /** @var int|string $offset */
                    $offset = $configuration['offset'];

                    /** @var int|string|null $length */
                    $length = $configuration['length'];

                    /** @var bool $preserveKeys */
                    $preserveKeys = $configuration['preserve_keys'];
                    $arguments = [(string) $offset];

                    if ($length !== null && $length !== '') {
                        $arguments[] = (string) $length;
                    } elseif ($preserveKeys) {
                        $arguments[] = 'null';
                    }

                    if ($preserveKeys) {
                        $arguments[] = 'true';
                    }

                    return $arguments;
                }),
            Filter::make('reverse')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.reverse.label'))
                ->icon(Heroicon::ArrowsUpDown)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.reverse.description'))
                ->color('gray')
                ->types('text', 'collection')
                ->schema([
                    Toggle::make('preserve_keys')
                        ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.reverse.preserve_keys'))
                        ->helperText(__(
                            'phpinnacle-stylus::forms.twig_editor.catalog.filters.reverse.preserve_keys_helper',
                        ))
                        ->default(false),
                ])
                ->argumentsUsing(static function (array $configuration) {
                    /** @var bool $preserveKeys */
                    $preserveKeys = $configuration['preserve_keys'];

                    return $preserveKeys ? ['true'] : [];
                }),
            Filter::make('shuffle')
                ->label(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.shuffle.label'))
                ->icon(Heroicon::ArrowsRightLeft)
                ->description(__('phpinnacle-stylus::forms.twig_editor.catalog.filters.shuffle.description'))
                ->color('gray')
                ->types('text', 'collection'),
        ];
    }

    private static function quoteTwigString(string $value): string
    {
        return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $value) . "'";
    }
}
