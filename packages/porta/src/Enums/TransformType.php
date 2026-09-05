<?php

namespace PHPinnacle\Porta\Enums;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Group;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\HtmlString;
use JsonPath\JsonObject;
use PHPinnacle\Intl\Forms\LocalePicker;
use PHPinnacle\Intl\Locales;
use Throwable;

enum TransformType: string
{
    case MAP = 'map';
    case DROP = 'drop';
    case MOVE = 'move';
    case CAST = 'cast';
    case TRIM = 'trim';
    case DATE = 'date';
    case RENAME = 'rename';
    case INSERT = 'insert';

    public static function resolve(string|self $value): self
    {
        return is_string($value) ? self::from($value) : $value;
    }

    public function form(): array
    {
        return match ($this) {
            self::MAP, self::RENAME, self::INSERT => [
                KeyValue::make('fields')
                    ->reorderable()
                    ->required(),
            ],
            self::MOVE => [
                TextInput::make('dest')
                    ->required(),
            ],
            self::CAST => [
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
            self::TRIM => [
                TextInput::make('chars'),
            ],
            self::DATE => [
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
            self::DROP => [],
        };
    }

    public function preview(array $config): string|HtmlString
    {
        return match ($this) {
            self::MAP, self::INSERT => Blade::render(
                '@foreach ($fields as $k => $v)<div><x-filament::badge color="success">{{ $k }} => {{ $v }}</x-filament::badge></div>@endforeach',
                [
                    'fields' => $config['fields'] ?? [],
                ],
            ),
            self::RENAME => Blade::render(
                '@foreach ($fields as $k => $v)<div><x-filament::badge color="warning">{{ $k }} => {{ $v }}</x-filament::badge></div>@endforeach',
                [
                    'fields' => $config['fields'] ?? [],
                ],
            ),
            self::MOVE => Blade::render(sprintf('<x-filament::badge>%s</x-filament::badge>', $config['dest'] ?? '$')),
            self::CAST => Blade::render(sprintf(
                '<x-filament::badge color="warning">%s</x-filament::badge>',
                $config['type'] ?? 'string',
            )),
            self::DROP => '<div class="fi-ta-placeholder text-sm leading-6 text-gray-400 dark:text-gray-500">-</div>',
            self::TRIM => filled($config['chars'] ?? null)
                ? Blade::render('@foreach ($chars as $char)<x-filament::badge>{{ $char }}</x-filament::badge>@endforeach', [
                    'chars' => str_split($config['chars']),
                ])
                : '<div class="fi-ta-placeholder text-sm leading-6 text-gray-400 dark:text-gray-500">-</div>',
            self::DATE => '<span class="fi-ta-placeholder text-sm leading-6 text-gray-400 dark:text-gray-500">'
                . (filled($config['input'] ?? null) ? $config['input'] : 'Any')
                . ' => '
                . (filled($config['output'] ?? null) ? $config['output'] : 'd.m.Y')
                . '</span>',
        };
    }

    public function transform(array $data, string $path, array $config): array
    {
        return match ($this) {
            self::MAP => $this->transformMap($data, $path, $config['fields'] ?? []),
            self::DROP => $this->transformDrop($data, $path),
            self::MOVE => $this->transformMove($data, $path, $config['dest'] ?? '$'),
            self::CAST => $this->transformCast($data, $path, $config['type'] ?? 'string'),
            self::TRIM => $this->transformTrim($data, $path, $config['chars'] ?? ''),
            self::DATE => $this->transformDate($data, $path, $config),
            self::RENAME => $this->transformRename($data, $path, $config['fields'] ?? []),
            self::INSERT => $this->transformInsert($data, $path, $config['fields'] ?? []),
        };
    }

    private function transformCast(array $data, string $path, string $type): array
    {
        $json = new JsonObject($data);
        $values = $json->get($path);

        if (!is_array($values)) {
            return $data;
        }

        foreach ($values as &$value) {
            settype($value, $type);
        }

        return (array) $json->getValue();
    }

    private function transformDate(array $data, string $path, array $config): array
    {
        $input = $config['input'] ?? '';
        $output = $config['output'] ?? DateTimeInterface::ATOM;
        $modify = $config['modify'] ?? '';
        $behavior = $config['behavior'] ?? 'skip';
        $locale = $config['locale'] ?? Locales::default();

        $json = new JsonObject($data);
        $values = $json->get($path);

        if (!is_array($values)) {
            return $data;
        }

        foreach ($values as &$value) {
            if (!is_string($value)) {
                continue;
            }

            try {
                $date = filled($input)
                    ? CarbonImmutable::createFromLocaleFormat($input, $locale, $value)
                    : CarbonImmutable::parseFromLocale($value, $locale);

                if (filled($modify)) {
                    $date = $date->modify($modify);
                }

                if ($config['future'] ?? false) {
                    $date = $date < Date::now() ? $date->addYear() : $date;
                }

                $value = $date->format($output);
            } catch (Throwable) {
                switch ($behavior) {
                    case 'now':
                        $value = Date::now()->format($output);

                        break;
                    case 'null':
                        $value = null;

                        break;
                    default:
                        // Do nothing
                        break;
                }
            }
        }

        return (array) $json->getValue();
    }

    private function transformDrop(array $data, string $path): array
    {
        $parts = explode('.', $path);
        $field = array_pop($parts);

        if ($field === null || $field === '') {
            return $data;
        }

        $json = new JsonObject($data);
        $values = $json->get(implode('.', $parts));

        if (!is_array($values)) {
            return $data;
        }

        foreach ($values as &$value) {
            if (!is_array($value)) {
                continue;
            }

            unset($value[$field]);
        }

        return (array) $json->getValue();
    }

    private function transformInsert(array $data, string $path, array $fields): array
    {
        $json = new JsonObject($data);
        $values = $json->get($path);

        if (!is_array($values)) {
            return $data;
        }

        foreach ($values as &$value) {
            if (!is_array($value)) {
                continue;
            }

            foreach ($fields as $key => $item) {
                $value[$key] = $item;
            }
        }

        return (array) $json->getValue();
    }

    private function transformMap(array $data, string $path, array $map): array
    {
        $json = new JsonObject($data);
        $values = $json->get($path);

        if (!is_array($values)) {
            return $data;
        }

        foreach ($values as &$value) {
            $type = gettype($value);

            if (!in_array($type, ['boolean', 'bool', 'integer', 'int', 'float', 'double', 'string'], strict: true)) {
                continue;
            }

            if (($map[(string) $value] ?? null) !== null) {
                $value = $map[$value];
            } elseif (($map['*'] ?? null) !== null) {
                $value = $map['*'];
            }

            settype($value, $type);
        }

        return (array) $json->getValue();
    }

    private function transformMove(array $data, string $path, string $dest): array
    {
        $json = new JsonObject($data);
        $values = $json->get($path);

        if (!is_array($values)) {
            return $data;
        }

        foreach ($values as $value) {
            $json->set($dest, $value);
        }

        return $this->transformDrop((array) $json->getValue(), $path);
    }

    private function transformRename(array $data, string $path, array $fields): array
    {
        $json = new JsonObject($data);
        $values = $json->get($path);

        if (!is_array($values)) {
            return $data;
        }

        foreach ($values as &$value) {
            if (!is_array($value)) {
                continue;
            }

            foreach ($fields as $from => $to) {
                if (!array_key_exists($from, $value)) {
                    continue;
                }

                $value[$to] = $value[$from];

                unset($value[$from]);
            }
        }

        return (array) $json->getValue();
    }

    private function transformTrim(array $data, string $path, string $chars): array
    {
        $json = new JsonObject($data);
        $values = $json->get($path);

        if (!is_array($values)) {
            return $data;
        }

        foreach ($values as &$value) {
            if (!is_string($value)) {
                continue;
            }

            $value = trim($value, $chars !== '' ? $chars : " \n\r\t\v\0");
        }

        return (array) $json->getValue();
    }
}
