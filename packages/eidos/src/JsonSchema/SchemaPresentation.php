<?php

namespace PHPinnacle\Eidos\JsonSchema;

use Filament\Support\Enums\IconSize;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;
use PHPinnacle\Jason\Schema;

use function Filament\Support\generate_icon_html;

/**
 * @phpstan-type Badge array{label: string, color: string}
 * @phpstan-type MappingItem array{
 *     id: string,
 *     name: string,
 *     label: string,
 *     icon: string|null,
 *     color: string|null,
 *     description: string|null,
 *     type: string,
 *     required: bool,
 *     badges: list<Badge>,
 *     presentation: Presentation
 * }
 * @phpstan-type Constraint array{kind: string, value: string, scope: list<string>}
 * @phpstan-type ValuePresentation array{type: string, nullable: bool, constraints: list<Constraint>, enum: list<string>}
 * @phpstan-type ItemPresentation array{scope: list<string>, type: string, nullable: bool, constraints: list<Constraint>, enum: list<string>}
 * @phpstan-type Presentation array{type: string, nullable: bool, constraints: list<Constraint>, enum: list<string>, items: list<ItemPresentation>}
 */
final class SchemaPresentation
{
    /**
     * @param array<string, mixed>|bool $definition
     * @return array{definition: array<string, mixed>|bool, propertyTitles: array<string, string>, icons: array<string, string>}
     */
    public static function render(array|bool $definition, bool $withIcons = true): array
    {
        $titles = [];
        $icons = [];

        Schema::fromArray($definition)
            ->resolveReferences()
            ->walk(function (Schema $schema, ?string $keyword, string|int|null $key) use (
                &$titles,
                &$icons,
                $withIcons,
            ) {
                if ($keyword === 'properties') {
                    $propertyName = (string) $key;
                    $titles[$propertyName] = Str::apa(Str::headline($propertyName));
                }

                $iconName = $schema->hasKeyword('x-icon') ? self::validateIcon($schema->keyword('x-icon')) : null;
                if ($schema->hasKeyword('x-color')) {
                    self::validateColor($schema->keyword('x-color'));
                }

                if (!$withIcons || $iconName === null || array_key_exists($iconName, $icons)) {
                    return;
                }

                /** @var Htmlable $icon */
                $icon = generate_icon_html(
                    $iconName,
                    attributes: new ComponentAttributeBag(['aria-hidden' => 'true']),
                    size: IconSize::Small,
                );
                $icons[$iconName] = $icon->toHtml();
            });

        return ['definition' => $definition, 'propertyTitles' => $titles, 'icons' => $icons];
    }

    /** @return list<MappingItem> */
    public static function toMappingItems(Schema $schema): array
    {
        $schema = $schema->resolveReferences();
        if ($schema->allowsAny() || $schema->deniesAny()) {
            throw new InvalidArgumentException('Boolean schemas cannot be used as mapping fields.');
        }

        $items = [];
        $required = $schema->effectiveRequired();
        foreach ($schema->effectiveProperties() as $name => $property) {
            if ($property->allowsAny() || $property->deniesAny()) {
                throw new InvalidArgumentException('Boolean schemas cannot be used as mapping fields.');
            }

            $name = (string) $name;
            /** @var string|null $icon */
            $icon = $property->annotation('x-icon');
            /** @var string|null $color */
            $color = $property->annotation('x-color');
            $items[] = [
                'id' => $name,
                'name' => $name,
                'label' => $property->annotation('title') ?? $name,
                'icon' => $icon,
                'color' => $color,
                'description' => $property->annotation('description'),
                'type' => self::label($property),
                'required' => in_array($name, $required, true),
                'badges' => self::badges($property),
                'presentation' => self::presentation($property),
            ];
        }

        return $items;
    }

    public static function label(Schema $schema, bool $formatted = false): string
    {
        $types = $schema->types();
        if ($formatted) {
            if (($schema->enum() ?? null) !== null) {
                return 'enum';
            }

            $alternatives = $schema->alternatives();
            if ($alternatives !== []) {
                $labels = array_unique(array_map(fn (Schema $branch) => self::label($branch, true), $alternatives));

                $label = implode(' | ', array_diff($labels, ['null']));

                return $label ? $label : 'null';
            }

            $format = $schema->effectiveFormat();
            if ($format !== null) {
                return $format;
            }

            $types = array_values(array_diff($types, ['null']));
        }

        $items = $schema->arrayItems();
        if ($items !== null) {
            $label = self::label($items, $formatted);

            return (
                (str_contains($label, ' | ') ? '(' . $label . ')' : $label)
                . '[]'
                . (!$formatted && in_array('null', $types, true) ? ' | null' : '')
            );
        }

        if ($types !== []) {
            return implode(' | ', $types);
        }

        return in_array('null', $schema->types(), true) ? 'null' : 'any';
    }

    /** @return Presentation */
    public static function presentation(Schema $schema): array
    {
        $schema = $schema->resolveReferences();

        return [...self::valuePresentation($schema), 'items' => self::itemPresentations($schema)];
    }

    /** @return ValuePresentation */
    private static function valuePresentation(Schema $schema): array
    {
        return [
            'type' => self::label($schema, true),
            'nullable' => count($schema->types()) > 1 && in_array('null', $schema->types(), true),
            'constraints' => self::constraints($schema),
            'enum' => array_map(
                fn (mixed $value) => (
                    self::enumLabel($schema, $value) ?? json_encode(
                        $value,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                    )
                ),
                $schema->enum() ?? [],
            ),
        ];
    }

    public static function enumLabel(Schema $schema, mixed $value): ?string
    {
        $key = is_string($value)
            ? $value
            : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        /** @var array<array-key, string> $labels */
        $labels = $schema->keyword('x-enum') ?? [];

        return $labels[$key] ?? null;
    }

    /**
     * @param list<string> $scope
     * @return list<ItemPresentation>
     */
    private static function itemPresentations(Schema $schema, array $scope = []): array
    {
        $items = $schema->arrayItems();
        if ($items === null) {
            return [];
        }

        $scope = [...$scope, 'items'];

        return [
            ['scope' => $scope, ...self::valuePresentation($items)],
            ...self::itemPresentations($items, $scope),
        ];
    }

    /**
     * @param list<string> $scope
     * @return list<Constraint>
     */
    private static function constraints(Schema $schema, array $scope = []): array
    {
        $constraints = [];
        foreach ([
            ['length', $schema->minLength(), $schema->maxLength()],
            ['items', $schema->minItems(), $schema->maxItems()],
        ] as [$kind, $min, $max]) {
            if ($min !== null || $max !== null) {
                $constraints[] = [
                    'kind' => $kind,
                    'value' => match (true) {
                        $min === $max => (string) $min,
                        $min !== null && $max !== null => "{$min}–{$max}",
                        $min !== null => "≥ {$min}",
                        default => "≤ {$max}",
                    },
                    'scope' => $scope,
                ];
            }
        }

        $range = [];
        foreach ([
            [$schema->minimum(), '≥'],
            [$schema->maximum(), '≤'],
            [$schema->exclusiveMinimum(), '>'],
            [$schema->exclusiveMaximum(), '<'],
        ] as [$value, $symbol]) {
            if ($value !== null) {
                $range[] = $symbol . ' ' . $value;
            }
        }

        if ($range !== []) {
            $constraints[] = ['kind' => 'range', 'value' => implode(' · ', $range), 'scope' => $scope];
        }

        if ($schema->hasKeyword('pattern')) {
            $constraints[] = [
                'kind' => 'pattern',
                'value' => '/' . $schema->pattern() . '/',
                'scope' => $scope,
            ];
        }

        if ($schema->hasKeyword('multipleOf')) {
            $constraints[] = [
                'kind' => 'multiple',
                'value' => '× ' . $schema->multipleOf(),
                'scope' => $scope,
            ];
        }

        if ($schema->uniqueItems() ?? false) {
            $constraints[] = ['kind' => 'unique', 'value' => '', 'scope' => $scope];
        }

        foreach ([
            'allOf' => $schema->branches('allOf'),
            'anyOf' => $schema->branches('anyOf'),
            'oneOf' => $schema->branches('oneOf'),
        ] as $keyword => $branches) {
            foreach ($branches as $index => $branch) {
                $constraints = [
                    ...$constraints,
                    ...self::constraints($branch, [...$scope, $keyword . ' ' . ($index + 1)]),
                ];
            }
        }

        return $constraints;
    }

    /** @return list<Badge> */
    public static function badges(Schema $schema): array
    {
        /** @var list<Badge> $badges */
        $badges = $schema->keyword('x-badges') ?? [];
        foreach ($schema->branches('allOf') as $branch) {
            $badges = [...$badges, ...self::badges($branch)];
        }

        return $badges;
    }

    private static function validateIcon(mixed $icon): string
    {
        if (!is_string($icon) || !preg_match('/^[a-zA-Z0-9_-]+$/D', $icon)) {
            throw new InvalidArgumentException('The JSON Schema [x-icon] must be a Blade icon name.');
        }

        return $icon;
    }

    private static function validateColor(mixed $color): void
    {
        $number = '[+-]?(?:\\d+(?:\\.\\d+)?|\\.\\d+)%?';
        $commaRgb = $number . '\\s*,\\s*' . $number . '\\s*,\\s*' . $number . '(?:\\s*,\\s*' . $number . ')?';
        $spaceRgb = $number . '\\s+' . $number . '\\s+' . $number . '(?:\\s*/\\s*' . $number . ')?';
        $pattern =
            '~^(?:[a-z][a-z0-9_-]*|\\#(?:[a-f0-9]{3,4}|[a-f0-9]{6}|[a-f0-9]{8})|rgba?\\(\\s*(?:'
            . $commaRgb
            . '|'
            . $spaceRgb
            . ')\\s*\\))$~iD';

        if (!is_string($color) || !preg_match($pattern, $color)) {
            throw new InvalidArgumentException(
                'The JSON Schema [x-color] must be a Filament color name, HEX or RGB/RGBA color.',
            );
        }
    }
}
